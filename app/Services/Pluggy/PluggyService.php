<?php

namespace App\Services\Pluggy;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LogicException;

class PluggyService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.pluggy.base_url'), '/');
    }

    public function getApiKey(): string
    {
        $this->ensureCredentialsAreConfigured();

        return Cache::remember(
            'pluggy.api-key',
            now()->addMinutes((int) config('services.pluggy.api_key_cache_minutes', 105)),
            function (): string {
                $response = $this->client()->post($this->baseUrl . '/auth', [
                    'clientId' => config('services.pluggy.client_id'),
                    'clientSecret' => config('services.pluggy.client_secret'),
                ]);

                $response->throw();
                $apiKey = $response->json('apiKey');

                if (! is_string($apiKey) || $apiKey === '') {
                    throw new LogicException('A Pluggy não retornou uma API Key válida.');
                }

                return $apiKey;
            },
        );
    }

    public function forgetApiKey(): void
    {
        Cache::forget('pluggy.api-key');
    }

    public function createConnectToken(string $clientUserId, ?string $itemId = null, bool $avoidDuplicates = true): string
    {
        $options = ['clientUserId' => $clientUserId, 'avoidDuplicates' => $avoidDuplicates];

        if ($webhookUrl = config('services.pluggy.webhook_url')) {
            $options['webhookUrl'] = $webhookUrl;
        }

        $payload = ['options' => $options];
        if ($itemId) {
            $payload['itemId'] = $itemId;
        }

        $response = $this->authenticatedClient()->post($this->baseUrl . '/connect_token', $payload);
        $response->throw();
        $token = $response->json('accessToken');

        if (! is_string($token) || $token === '') {
            throw new LogicException('A Pluggy não retornou um Connect Token válido.');
        }

        return $token;
    }

    /** @return array<string, mixed> */
    public function getItem(string $itemId): array
    {
        $response = $this->authenticatedClient()->get($this->baseUrl . '/items/' . $itemId);
        if ($response->status() === 404) {
            throw new PluggyItemNotFoundException('O item não foi encontrado.');
        }
        $response->throw();

        return $response->json();
    }

    /** @return array<string, mixed> */
    public function syncItem(string $itemId): array
    {
        $response = $this->authenticatedClient()->patch($this->baseUrl . '/items/' . $itemId, []);
        $response->throw();

        return $response->json();
    }

    /** @return array<string, mixed> */
    public function assignItemToClientUser(string $itemId, string $clientUserId): array
    {
        $payload = ['clientUserId' => $clientUserId];
        if ($webhookUrl = config('services.pluggy.webhook_url')) {
            $payload['webhookUrl'] = $webhookUrl;
        }

        $response = $this->authenticatedClient()->patch($this->baseUrl . '/items/' . $itemId, $payload);
        $response->throw();

        return $response->json();
    }

    /** @return list<array<string, mixed>> */
    public function getAccountsForItem(string $itemId): array
    {
        $accounts = [];
        $page = 1;

        do {
            $response = $this->authenticatedClient()->get($this->baseUrl . '/accounts', [
                'itemId' => $itemId,
                'page' => $page,
                'pageSize' => 500,
            ]);
            $response->throw();
            $payload = $response->json();
            if (! is_array($payload) || ! isset($payload['results']) || ! is_array($payload['results']) || ! array_is_list($payload['results'])) {
                throw new LogicException('Resposta de contas inválida.');
            }
            $accounts = [...$accounts, ...($payload['results'] ?? [])];
            $totalPages = max(1, (int) ($payload['totalPages'] ?? 1));
            $page++;
        } while ($page <= $totalPages);

        return $accounts;
    }

    /** @param list<string> $itemIds
     *  @return list<array{itemId: string, accounts: list<array<string, mixed>>}>
     */
    public function getAccounts(array $itemIds): array
    {
        return array_map(fn (string $itemId) => [
            'itemId' => $itemId,
            'accounts' => $this->getAccountsForItem($itemId),
        ], $itemIds);
    }

    /** @return list<array<string, mixed>> */
    public function getTransactions(string $accountId): array
    {
        $transactions = [];
        $next = '?accountId=' . rawurlencode($accountId);
        $visited = [];

        do {
            if (isset($visited[$next])) {
                throw new LogicException('Cursor de transações repetido.');
            }
            $visited[$next] = true;
            $response = $this->authenticatedClient()->get($this->baseUrl . '/v2/transactions' . $next);
            $response->throw();
            $payload = $response->json();
            if (! is_array($payload) || ! isset($payload['results']) || ! is_array($payload['results']) || ! array_is_list($payload['results'])) {
                throw new LogicException('Resposta de transações inválida.');
            }
            $transactions = [...$transactions, ...($payload['results'] ?? [])];
            $next = $payload['next'] ?? null;

            if ($next !== null && (! is_string($next) || ! str_starts_with($next, '?'))) {
                throw new LogicException('A Pluggy retornou um cursor de transações inválido.');
            }
        } while ($next !== null);

        return $transactions;
    }

    /** Returns true only when the authenticated transaction endpoint confirms a 404. */
    public function isTransactionMissing(string $transactionId): bool
    {
        $response = $this->authenticatedClient()->get($this->baseUrl . '/transactions/' . rawurlencode($transactionId));
        if ($response->status() === 404) {
            return true;
        }
        $response->throw();
        $payload = $response->json();
        if (! is_array($payload) || ($payload['id'] ?? null) !== $transactionId) {
            throw new LogicException('Resposta de transação inválida.');
        }
        return false;
    }

    private function authenticatedClient(): PendingRequest
    {
        return $this->client()->withHeaders(['X-API-KEY' => $this->getApiKey()]);
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->asJson()->timeout(15)->retry(2, 300, throw: false);
    }

    private function ensureCredentialsAreConfigured(): void
    {
        if (! config('services.pluggy.client_id') || ! config('services.pluggy.client_secret')) {
            throw new LogicException('As credenciais da Pluggy não estão configuradas.');
        }
    }
}
