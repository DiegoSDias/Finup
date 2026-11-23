<?php

namespace Tests\Feature;

use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use App\Services\Pluggy\InternalTransferMatcher;
use App\Services\Pluggy\PluggyTransactionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluggyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_is_idempotent_and_pending_entries_do_not_affect_totals(): void
    {
        [$connection, $account] = $this->connectionAndAccount();
        $importer = app(PluggyTransactionImporter::class);
        $transaction = $this->transaction('a34715d1-a440-4360-bc75-916fe6d9ee00', 'DEBIT', 'POSTED');

        $first = $importer->import($connection, $account, [$transaction]);
        $second = $importer->import($connection, $account, [$transaction]);
        $importer->import($connection, $account, [$this->transaction('b34715d1-a440-4360-bc75-916fe6d9ee00', 'DEBIT', 'PENDING')]);

        $this->assertSame(['created' => 1, 'updated' => 0], $first);
        $this->assertSame(['created' => 0, 'updated' => 1], $second);
        $this->assertDatabaseCount('pluggy_transactions', 2);
        $this->assertDatabaseHas('pluggy_transactions', [
            'pluggy_transaction_id' => 'b34715d1-a440-4360-bc75-916fe6d9ee00',
            'affects_totals' => false,
        ]);
    }

    public function test_strong_pix_pair_is_excluded_as_internal_transfer(): void
    {
        [$connection, $source] = $this->connectionAndAccount();
        $destination = PluggyAccount::create([
            'user_id' => $connection->user_id,
            'pluggy_connection_id' => $connection->id,
            'pluggy_account_id' => 'c34715d1-a440-4360-bc75-916fe6d9ee00',
            'name' => 'Conta destino', 'type' => 'BANK', 'currency_code' => 'BRL', 'balance' => 0,
        ]);
        $importer = app(PluggyTransactionImporter::class);
        $debit = $this->transaction('d34715d1-a440-4360-bc75-916fe6d9ee00', 'DEBIT', 'POSTED');
        $credit = $this->transaction('e34715d1-a440-4360-bc75-916fe6d9ee00', 'CREDIT', 'POSTED');
        $debit['paymentData'] = ['paymentMethod' => 'PIX', 'referenceNumber' => 'E123456789'];
        $credit['paymentData'] = ['paymentMethod' => 'PIX', 'referenceNumber' => 'E123456789'];

        $importer->import($connection, $source, [$debit]);
        $importer->import($connection, $destination, [$credit]);

        $this->assertSame(1, app(InternalTransferMatcher::class)->matchForUser($connection->user_id));
        $this->assertSame(2, PluggyTransaction::query()->where('is_internal_transfer', true)->count());
        $this->assertSame(0, PluggyTransaction::query()->where('affects_totals', true)->count());
    }

    public function test_same_amount_without_a_transfer_reference_is_not_paired(): void
    {
        [$connection, $source] = $this->connectionAndAccount();
        $destination = PluggyAccount::create([
            'user_id' => $connection->user_id,
            'pluggy_connection_id' => $connection->id,
            'pluggy_account_id' => 'b44715d1-a440-4360-bc75-916fe6d9ee00',
            'name' => 'Outra conta', 'type' => 'BANK', 'currency_code' => 'BRL', 'balance' => 0,
        ]);
        $importer = app(PluggyTransactionImporter::class);

        $importer->import($connection, $source, [$this->transaction('c44715d1-a440-4360-bc75-916fe6d9ee00', 'DEBIT', 'POSTED')]);
        $importer->import($connection, $destination, [$this->transaction('d44715d1-a440-4360-bc75-916fe6d9ee00', 'CREDIT', 'POSTED')]);

        $this->assertSame(0, app(InternalTransferMatcher::class)->matchForUser($connection->user_id));
        $this->assertSame(0, PluggyTransaction::query()->where('is_internal_transfer', true)->count());
    }

    /** @return array{PluggyItem, PluggyAccount} */
    private function connectionAndAccount(): array
    {
        $user = User::factory()->create();
        $connection = PluggyItem::create([
            'user_id' => $user->id,
            'pluggy_item_id' => 'f34715d1-a440-4360-bc75-916fe6d9ee00',
            'last_event' => 'item/created',
        ]);
        $account = PluggyAccount::create([
            'user_id' => $user->id,
            'pluggy_connection_id' => $connection->id,
            'pluggy_account_id' => 'a44715d1-a440-4360-bc75-916fe6d9ee00',
            'name' => 'Conta origem', 'type' => 'BANK', 'currency_code' => 'BRL', 'balance' => 0,
        ]);

        return [$connection, $account];
    }

    /** @return array<string, mixed> */
    private function transaction(string $id, string $type, string $status): array
    {
        return [
            'id' => $id,
            'description' => 'Transferência de teste',
            'amount' => 100,
            'currencyCode' => 'BRL',
            'date' => '2026-09-29T12:00:00-03:00',
            'type' => $type,
            'status' => $status,
        ];
    }
}
