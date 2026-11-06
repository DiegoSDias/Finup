<?php

namespace Tests\Feature;

use App\Models\AccountPayable;
use App\Models\Category;
use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountPayableTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payable_does_not_create_an_expense_or_affect_real_totals(): void
    {
        $user = User::factory()->create();
        $category = $this->category($user);
        $this->actingAs($user)->post(route('accounts-payable.store'), $this->payload($category))->assertRedirect();

        $this->assertDatabaseCount('accounts_payable', 1);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertSame(0.0, app(\App\Services\MonthlyBalanceService::class)->calculate(2027, 1, $user->id)['expenses']);
    }

    public function test_manual_settlement_creates_exactly_one_linked_expense_and_can_be_reopened(): void
    {
        $user = User::factory()->create();
        $payable = AccountPayable::create([...$this->payload($this->category($user)), 'user_id' => $user->id]);
        $response = $this->actingAs($user)->post(route('accounts-payable.settle-manually', $payable), ['payment_date' => '2027-01-12', 'payment_mode' => 'pix', 'bank_choice' => 'none']);
        $response->assertSessionHasNoErrors()->assertRedirect(route('month.show', [2027, 1]));

        $this->assertDatabaseCount('expenses', 1);
        $this->assertDatabaseCount('payable_settlements', 1);
        $this->assertSame(AccountPayable::STATUS_PAID, $payable->fresh()->status);
        $this->assertSame(120.0, app(\App\Services\MonthlyBalanceService::class)->calculate(2027, 1, $user->id)['expensesPix']);

        $this->post(route('accounts-payable.settle-manually', $payable), ['payment_date' => '2027-01-12', 'payment_mode' => 'pix', 'bank_choice' => 'none'])
            ->assertSessionHasErrors('payable');
        $this->assertDatabaseCount('expenses', 1);
        $this->post(route('accounts-payable.reopen', $payable))->assertSessionHasNoErrors();
        $this->assertSame(AccountPayable::STATUS_PENDING, $payable->fresh()->status);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('payable_settlements', 0);
    }

    public function test_pluggy_settlement_links_existing_transaction_without_creating_a_manual_expense(): void
    {
        $user = User::factory()->create();
        $payable = AccountPayable::create([...$this->payload($this->category($user)), 'user_id' => $user->id]);
        $transaction = $this->pluggyExpense($user, 'pluggy-transaction-1');
        $this->actingAs($user)->post(route('accounts-payable.settle-pluggy', $payable), ['pluggy_transaction_id' => $transaction->id])->assertSessionHasNoErrors();

        $this->assertSame(AccountPayable::STATUS_PAID, $payable->fresh()->status);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseHas('payable_settlements', ['account_payable_id' => $payable->id, 'pluggy_transaction_id' => $transaction->id]);
    }

    public function test_payment_cannot_link_another_users_transaction_or_reuse_one_transaction(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $first = AccountPayable::create([...$this->payload($this->category($user)), 'user_id' => $user->id]);
        $second = AccountPayable::create([...$this->payload($this->category($user)), 'user_id' => $user->id]);
        $foreign = $this->pluggyExpense($other, 'pluggy-foreign');
        $own = $this->pluggyExpense($user, 'pluggy-own');

        $this->actingAs($user)->post(route('accounts-payable.settle-pluggy', $first), ['pluggy_transaction_id' => $foreign->id])->assertSessionHasErrors('pluggy_transaction_id');
        $this->post(route('accounts-payable.settle-pluggy', $first), ['pluggy_transaction_id' => $own->id])->assertSessionHasNoErrors();
        $this->post(route('accounts-payable.settle-pluggy', $second), ['pluggy_transaction_id' => $own->id])->assertSessionHasErrors('pluggy_transaction_id');
    }

    public function test_payables_are_private_and_overdue_is_derived_from_due_date(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $payable = AccountPayable::create([...$this->payload($this->category($user), ['due_date' => now()->subDay()->toDateString()]), 'user_id' => $user->id]);
        $this->assertSame('overdue', $payable->display_status);
        $this->actingAs($other)->get(route('accounts-payable.show', $payable))->assertNotFound();
    }

    public function test_cancelled_payable_can_be_deleted_but_paid_payable_cannot(): void
    {
        $user = User::factory()->create();
        $cancelled = AccountPayable::create([...$this->payload($this->category($user)), 'user_id' => $user->id, 'status' => AccountPayable::STATUS_CANCELLED]);
        $paid = AccountPayable::create([...$this->payload($this->category($user), ['description' => 'Internet']), 'user_id' => $user->id, 'status' => AccountPayable::STATUS_PAID]);

        $this->actingAs($user)->get(route('accounts-payable.show', $cancelled))->assertOk()->assertSee('Excluir conta cancelada');
        $this->delete(route('accounts-payable.destroy', $cancelled))->assertRedirect(route('accounts-payable.index'));
        $this->assertDatabaseMissing('accounts_payable', ['id' => $cancelled->id]);

        $this->delete(route('accounts-payable.destroy', $paid))->assertSessionHasErrors('payable');
        $this->assertDatabaseHas('accounts_payable', ['id' => $paid->id]);
    }

    public function test_payable_rejects_a_category_owned_by_another_user_and_migrations_block_data_loss_on_rollback(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreignCategory = $this->category($other);
        $this->actingAs($user)->post(route('accounts-payable.store'), $this->payload($foreignCategory))->assertSessionHasErrors('category_id');

        AccountPayable::create([...$this->payload($this->category($user)), 'user_id' => $user->id]);
        $migration = require database_path('migrations/2026_11_06_000000_create_accounts_payable_table.php');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reversão bloqueada');
        $migration->down();
    }

    private function category(User $user): Category
    {
        return Category::create(['user_id' => $user->id, 'name' => 'Moradia', 'type' => 'expense']);
    }

    private function payload(Category $category, array $extra = []): array
    {
        return array_merge(['description' => 'Conta de luz', 'amount' => '120.00', 'due_date' => '2027-01-10', 'category_id' => $category->id, 'status' => AccountPayable::STATUS_PENDING], $extra);
    }

    private function pluggyExpense(User $user, string $id): PluggyTransaction
    {
        $item = PluggyItem::create(['user_id' => $user->id, 'pluggy_item_id' => (string) \Illuminate\Support\Str::uuid(), 'last_event' => 'item/created', 'is_active' => true]);
        $account = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => (string) \Illuminate\Support\Str::uuid(), 'name' => 'Conta', 'type' => 'BANK', 'is_active' => true]);

        return PluggyTransaction::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => $account->id, 'pluggy_transaction_id' => $id, 'description' => 'Conta de luz', 'transaction_date' => '2027-01-12', 'amount' => 120, 'direction' => 'DEBIT', 'status' => 'POSTED', 'classification' => 'expense', 'affects_totals' => true]);
    }
}
