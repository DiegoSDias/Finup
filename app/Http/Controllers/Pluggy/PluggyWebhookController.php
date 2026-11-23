<?php

namespace App\Http\Controllers\Pluggy;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPluggyWebhook;
use App\Models\PluggyItem;
use App\Models\PluggyWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PluggyWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        if (! $request->isJson()) {
            return response()->noContent(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        $secret = config('services.pluggy.webhook_secret');
        $provided = (string) ($request->header('X-Pluggy-Webhook-Secret')
            ?: (config('services.pluggy.webhook_allow_query_token') ? $request->query('token') : ''));
        if (! is_string($secret) || $secret === '' || ! hash_equals($secret, $provided)) {
            return response()->noContent(Response::HTTP_UNAUTHORIZED);
        }

        $event = $request->json()->all();
        $eventName = $event['event'] ?? null;
        $eventId = $event['eventId'] ?? null;
        $itemId = $event['itemId'] ?? null;

        if (! is_string($eventName) || $eventName === '' || strlen($eventName) > 80 || ! is_string($eventId) || ! Str::isUuid($eventId)
            || ($itemId !== null && (! is_string($itemId) || ! Str::isUuid($itemId)))
            || ((str_starts_with($eventName, 'item/') || str_starts_with($eventName, 'transactions/')) && ! $itemId)) {
            return response()->noContent(Response::HTTP_BAD_REQUEST);
        }

        $ids = $event['transactionIds'] ?? [];
        $clientId = $event['clientUserId'] ?? null;
        if ($clientId !== null && (! is_string($clientId) && ! is_int($clientId)
            || strlen((string) $clientId) > 20 || ! ctype_digit((string) $clientId))) {
            return response()->noContent(Response::HTTP_BAD_REQUEST);
        }
        if (! is_array($ids) || ! array_is_list($ids) || count($ids) > 1000
            || collect($ids)->contains(fn ($id) => ! is_string($id) || ! Str::isUuid($id))) {
            return response()->noContent(Response::HTTP_BAD_REQUEST);
        }

        if ($itemId && str_starts_with($eventName, 'item/')) {
            $existing = PluggyItem::withTrashed()->where('pluggy_item_id', $itemId)->first();
            $clientUserId = filter_var($event['clientUserId'] ?? null, FILTER_VALIDATE_INT);
            if ($existing && $clientUserId && (int) $existing->user_id !== $clientUserId) {
                return response()->noContent(Response::HTTP_UNAUTHORIZED);
            }
        }

        // Database queue and event must share a transaction; sync and external queue drivers
        // cannot provide this delivery guarantee.
        $queueName = config('queue.default');
        $queue = config("queue.connections.$queueName");
        if (($queue['driver'] ?? null) !== 'database'
            || (($queue['connection'] ?? null) !== null && $queue['connection'] !== config('database.default'))) {
            Log::error('Fila de webhook Pluggy não é transacional com o banco padrão.');
            return response()->noContent(Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $payload = ['itemId' => $itemId, 'clientUserId' => $clientId, 'transactionIds' => $ids];
        try {
            $accepted = DB::transaction(function () use ($eventId, $eventName, $itemId, $payload) {
                $saved = PluggyWebhookEvent::query()->firstOrCreate(
                    ['event_id' => $eventId],
                    ['event' => $eventName, 'pluggy_item_id' => $itemId, 'payload' => $payload, 'received_at' => now()],
                );
                if (! $saved->wasRecentlyCreated) {
                    return $saved->event === $eventName && $saved->pluggy_item_id === $itemId;
                }
                ProcessPluggyWebhook::dispatch($saved->id)->beforeCommit();
                return true;
            });
            return response()->noContent($accepted ? 200 : 409);
        } catch (\Throwable $exception) {
            Log::error('Falha ao registrar webhook Pluggy.', ['exception' => $exception::class]);
            return response()->noContent(Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
