<?php

namespace App\Jobs;

use App\Models\PluggyItem;
use App\Models\PluggyWebhookEvent;
use App\Services\Pluggy\PluggySyncService;
use App\Services\Pluggy\PluggyService;
use App\Models\User;
use App\Models\PluggyTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessPluggyWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    public int $timeout = 240;

    public function __construct(public int $eventId)
    {
    }

    public function handle(PluggySyncService $syncService, PluggyService $pluggy): void
    {
        $lock = Cache::store('database')->lock('pluggy-webhook:'.$this->eventId, 300);
        if (! $lock->get()) {
            throw new \RuntimeException('O evento já está em processamento.');
        }
        try {
            $this->process($syncService, $pluggy);
        } finally {
            $lock->release();
        }
    }

    private function process(PluggySyncService $syncService, PluggyService $pluggy): void
    {
        $event = PluggyWebhookEvent::query()->find($this->eventId);
        if (! $event || $event->processed_at) {
            return;
        }

        try {
            $payload = $event->payload;
            $itemId = $payload['itemId'] ?? null;

            $connection = $itemId ? PluggyItem::withTrashed()->where('pluggy_item_id', $itemId)->first() : null;
            $clientUserId = filter_var($payload['clientUserId'] ?? null, FILTER_VALIDATE_INT);
            if ($connection && $clientUserId && (int) $connection->user_id !== $clientUserId) {
                throw new \RuntimeException('O proprietário do evento não corresponde à conexão.');
            }
            if (! $connection && $itemId && $event->event === 'item/created' && $clientUserId && User::query()->whereKey($clientUserId)->exists()) {
                $remote = $pluggy->getItem($itemId);
                if ((string) ($remote['clientUserId'] ?? '') !== (string) $clientUserId) {
                    throw new \RuntimeException('Não foi possível confirmar o proprietário da conexão.');
                }
                $connection = PluggyItem::query()->firstOrCreate(['pluggy_item_id' => $itemId], [
                    'user_id' => $clientUserId, 'last_event' => 'item/created', 'is_active' => true,
                ]);
                if ((int) $connection->user_id !== $clientUserId) {
                    throw new \RuntimeException('O proprietário da conexão não corresponde ao evento.');
                }
            }
            if (! $connection && $itemId && (str_starts_with($event->event, 'item/') || str_starts_with($event->event, 'transactions/'))) {
                throw new \RuntimeException('A conexão do evento ainda não está vinculada.');
            }
            if ($event->event === 'transactions/deleted') {
                if ($connection && $connection->is_active && ! $connection->trashed()) {
                    $this->reconcileDeletedTransactions($connection, $payload['transactionIds'] ?? [], $syncService, $pluggy);
                }
            }
            if ($connection && $connection->is_active && ! $connection->trashed()
                && in_array($event->event, ['item/created', 'item/updated', 'item/deleted', 'item/error', 'item/waiting_user_input', 'item/waiting_user_action', 'transactions/created', 'transactions/updated'], true)) {
                // Always read the current provider state instead of applying a potentially stale event.
                $syncService->sync($connection);
            }

            $event->update(['processed_at' => now(), 'processing_error' => null]);
        } catch (Throwable $exception) {
            $event->update(['processing_error' => 'Falha ao processar o evento. Consulte os logs técnicos.']);
            Log::warning('Falha no processamento de webhook Pluggy.', ['event_id' => $event->id, 'exception' => $exception::class]);
            throw new \RuntimeException('Falha ao processar o evento Pluggy.');
        }
    }

    private function reconcileDeletedTransactions(PluggyItem $connection, array $ids, PluggySyncService $syncService, PluggyService $pluggy): void
    {
        $lock = Cache::store('database')->lock('pluggy-sync:'.$connection->id, 300);
        if (! $lock->get()) {
            throw new \RuntimeException('Já existe uma sincronização em andamento.');
        }
        try {
            $item = $pluggy->getItem($connection->pluggy_item_id);
            if (($item['status'] ?? null) !== 'UPDATED' || ($item['executionStatus'] ?? null) !== 'SUCCESS') {
                throw new \RuntimeException('A conexão não está pronta para conciliar exclusões.');
            }
            $candidates = PluggyTransaction::query()->where('user_id', $connection->user_id)
                ->where('pluggy_connection_id', $connection->id)->whereNull('removed_at')
                ->whereIn('pluggy_transaction_id', $ids)->pluck('pluggy_transaction_id');
            $confirmed = [];
            foreach ($candidates as $id) {
                if ($pluggy->isTransactionMissing($id)) {
                    $confirmed[] = $id;
                }
            }
            if ($confirmed) {
                DB::transaction(function () use ($connection, $syncService, $confirmed): void {
                    $current = PluggyItem::query()->lockForUpdate()->find($connection->id);
                    if ($current && $current->is_active) {
                        $syncService->markTransactionsDeleted($current->pluggy_item_id, $confirmed);
                    }
                });
            }
        } finally {
            $lock->release();
        }
    }
}
