<?php

namespace App\Services\Pluggy;

use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Services\MonthlyBalanceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PluggySyncService
{
    public function __construct(
        private readonly PluggyService $pluggy,
        private readonly PluggyTransactionImporter $importer,
        private readonly InternalTransferMatcher $transferMatcher,
    ) {
    }

    /** @return array{accounts: int, created: int, updated: int, transfers: int, status: string} */
    public function sync(PluggyItem $connection, bool $requestRemoteSync = false): array
    {
        $lock = Cache::store('database')->lock('pluggy-sync:'.$connection->id, 300);
        if (! $lock->get()) {
            throw new RuntimeException('Já existe uma sincronização em andamento para esta conexão.');
        }
        try {
            return $this->syncLocked($connection, $requestRemoteSync);
        } finally {
            $lock->release();
        }
    }

    private function syncLocked(PluggyItem $connection, bool $requestRemoteSync): array
    {
        $connection->update(['last_sync_started_at' => now(), 'last_sync_status' => 'running', 'last_error' => null]);
        Log::info('Sincronização Pluggy iniciada.', ['connection_id' => $connection->id]);

        $stage = 'item';
        try {
            $item = $this->pluggy->getItem($connection->pluggy_item_id);

            // O conector MeuPluggy expõe os dados já prontos, mas rejeita PATCH /items.
            // Para ele, uma sincronização manual significa apenas importar o estado atual.
            if ($requestRemoteSync && ! $this->isMeuPluggy($item)) {
                $stage = 'request_update';
                $item = $this->pluggy->syncItem($connection->pluggy_item_id);
            }

            $this->updateConnectionFromItem($connection, $item);

            $status = strtoupper((string) ($item['status'] ?? ''));
            $execution = strtoupper((string) ($item['executionStatus'] ?? ''));
            if ($status === 'LOGIN_ERROR' || $execution === 'INVALID_CREDENTIALS') {
                $connection->update(['last_sync_status' => 'needs_reconnection', 'last_error' => 'A instituição exige que você revise a conexão.']);
                return $this->emptyResult('needs_reconnection');
            }
            if ($status === 'WAITING_USER_INPUT') {
                $connection->update(['last_sync_status' => 'needs_action', 'last_error' => 'A instituição solicita uma ação para continuar.']);
                return $this->emptyResult('needs_action');
            }
            if ($execution === 'PARTIAL_SUCCESS') {
                $connection->update(['last_sync_status' => 'partial', 'last_error' => 'A instituição retornou uma atualização parcial; os dados anteriores foram preservados.']);
                return $this->emptyResult('partial');
            }
            if ($status === 'OUTDATED' || in_array($execution, ['ERROR', 'CONNECTION_ERROR'], true)) {
                $this->markFailed($connection, 'A instituição não concluiu a atualização. Tente novamente mais tarde.', null);
                return $this->emptyResult('failed');
            }
            if ($status === 'UPDATING' || ($execution !== '' && $execution !== 'SUCCESS')) {
                $connection->update(['last_sync_status' => 'awaiting_provider', 'last_error' => null]);
                return $this->emptyResult('awaiting_provider');
            }

            $stage = 'accounts';
            $remoteAccounts = $this->pluggy->getAccountsForItem($connection->pluggy_item_id);
            if ($remoteAccounts === [] && $connection->accounts()->where('is_active', true)->exists()) {
                $connection->update(['last_sync_status' => 'partial', 'last_error' => 'Nenhuma conta foi retornada; os dados anteriores foram preservados.']);
                return $this->emptyResult('partial');
            }
            $transactions = [];
            foreach ($remoteAccounts as $remoteAccount) {
                if (empty($remoteAccount['id'])) {
                    throw new RuntimeException('Conta recebida sem identificador.');
                }
                $stage = 'transactions';
                $transactions[$remoteAccount['id']] = $this->pluggy->getTransactions($remoteAccount['id']);
            }

            return DB::transaction(function () use ($connection, $remoteAccounts, $transactions) {
            $connection = PluggyItem::query()->lockForUpdate()->find($connection->id);
            if (! $connection || ! $connection->is_active) {
                return $this->emptyResult('disconnected');
            }
            $remoteAccountIds = [];
            $created = 0;
            $updated = 0;
            foreach ($remoteAccounts as $remoteAccount) {
                $account = $this->upsertAccount($connection, $remoteAccount);
                $remoteAccountIds[] = $account->pluggy_account_id;
                $result = $this->importer->import($connection, $account, $transactions[$account->pluggy_account_id]);
                $created += $result['created'];
                $updated += $result['updated'];
            }
            if ($remoteAccountIds !== []) {
            $connection->accounts()
                ->where('user_id', $connection->user_id)
                ->whereNotIn('pluggy_account_id', $remoteAccountIds)
                ->update(['is_active' => false]);
            }

            $transfers = $this->transferMatcher->matchForUser($connection->user_id);
            app(MonthlyBalanceService::class)->recalculateClosedFrom($connection->user_id, 0, 1);
            $connection->update(['last_synced_at' => now(), 'last_sync_status' => 'success', 'last_error' => null]);

            Log::info('Sincronização Pluggy concluída.', [
                'connection_id' => $connection->id,
                'accounts' => count($remoteAccounts),
                'created' => $created,
                'updated' => $updated,
                'internal_transfers' => $transfers,
            ]);

            return ['accounts' => count($remoteAccounts), 'created' => $created, 'updated' => $updated, 'transfers' => $transfers, 'status' => 'success'];
            });
        } catch (PluggyItemNotFoundException $exception) {
            $connection->update(['last_sync_status' => 'needs_reconnection', 'last_error' => 'A conexão não foi encontrada no provedor. Revise a conexão.']);
            return $this->emptyResult('needs_reconnection');
        } catch (RequestException $exception) {
            $status = $exception->response?->status();
            $this->markFailed($connection, 'Não foi possível atualizar os dados bancários. Os dados anteriores foram preservados.', $status);
            Log::warning('Etapa de sincronização Pluggy com erro.', ['connection_id' => $connection->id, 'stage' => $stage, 'status' => $status]);
            if ($status && $status >= 400 && $status < 500 && $status !== 429) {
                return $this->emptyResult('failed');
            }
            throw new RuntimeException('Falha temporária na sincronização Pluggy.');
        } catch (Throwable $exception) {
            $this->markFailed($connection, 'Não foi possível concluir a sincronização.', null);
            Log::warning('Erro na sincronização Pluggy.', ['connection_id' => $connection->id, 'stage' => $stage, 'exception' => $exception::class]);
            throw new RuntimeException('Falha na sincronização Pluggy.');
        }
    }

    private function emptyResult(string $status): array
    {
        return ['accounts' => 0, 'created' => 0, 'updated' => 0, 'transfers' => 0, 'status' => $status];
    }

    public function markTransactionsDeleted(string $pluggyItemId, array $transactionIds): int
    {
        $connection = PluggyItem::query()->where('pluggy_item_id', $pluggyItemId)->first();
        if (! $connection) {
            return 0;
        }
        $updated = PluggyTransaction::query()
            ->whereHas('connection', fn ($query) => $query->where('pluggy_item_id', $pluggyItemId)->whereColumn('pluggy_items.user_id', 'pluggy_transactions.user_id'))
            ->whereIn('pluggy_transaction_id', $transactionIds)
            ->update(['removed_at' => now(), 'affects_totals' => false]);
        app(MonthlyBalanceService::class)->recalculateClosedFrom($connection->user_id, 0, 1);
        return $updated;
    }

    /** @param array<string, mixed> $item */
    private function updateConnectionFromItem(PluggyItem $connection, array $item): void
    {
        $connector = $item['connector'] ?? [];
        $connection->update([
            'connector_id' => $connector['id'] ?? $connection->connector_id,
            'connector_name' => $connector['name'] ?? $connection->connector_name,
            'connector_image_url' => $connector['imageUrl'] ?? $connection->connector_image_url,
            'status' => $item['status'] ?? $connection->status,
            'execution_status' => $item['executionStatus'] ?? $connection->execution_status,
            'last_event' => (($item['status'] ?? null) === 'UPDATED') ? 'item/updated' : $connection->last_event,
            'last_error' => null,
        ]);
    }

    /** @param array<string, mixed> $remoteAccount */
    private function upsertAccount(PluggyItem $connection, array $remoteAccount): PluggyAccount
    {
        $existing = $connection->accounts()->where('pluggy_account_id', $remoteAccount['id'])->first();
        if ($existing && (int) $existing->user_id !== (int) $connection->user_id) {
            throw new \LogicException('A conta existente não pertence ao proprietário da conexão.');
        }

        return PluggyAccount::query()->updateOrCreate(
            ['pluggy_connection_id' => $connection->id, 'pluggy_account_id' => $remoteAccount['id']],
            [
                'user_id' => $connection->user_id,
                'name' => $remoteAccount['name'] ?? 'Conta conectada',
                'type' => $remoteAccount['type'] ?? 'BANK',
                'subtype' => $remoteAccount['subtype'] ?? null,
                'number_last_four' => $this->lastFour($remoteAccount['number'] ?? null),
                'currency_code' => $remoteAccount['currencyCode'] ?? 'BRL',
                'balance' => $remoteAccount['balance'] ?? 0,
                'owner_document_hash' => $this->fingerprint($remoteAccount['taxNumber'] ?? null),
                'account_number_hash' => $this->fingerprint($remoteAccount['number'] ?? null),
                'routing_number_hash' => $this->fingerprint(data_get($remoteAccount, 'bankData.transferNumber')),
                'is_active' => true,
                'last_synced_at' => now(),
            ],
        );
    }

    private function lastFour(?string $number): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $number);

        return $digits ? substr($digits, -4) : null;
    }

    private function fingerprint(?string $value): ?string
    {
        $normalized = preg_replace('/\D/', '', (string) $value);

        return $normalized ? hash_hmac('sha256', $normalized, (string) config('app.key')) : null;
    }

    private function markFailed(PluggyItem $connection, string $message, ?int $status): void
    {
        $connection->update(['last_sync_status' => 'failed', 'last_error' => $message]);
        Log::warning('Sincronização Pluggy falhou.', ['connection_id' => $connection->id, 'status' => $status]);
    }

    /** @param array<string, mixed> $item */
    private function isMeuPluggy(array $item): bool
    {
        return str_contains(strtolower((string) data_get($item, 'connector.name')), 'meupluggy');
    }
}
