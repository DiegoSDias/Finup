<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use App\Services\MonthlyBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstallmentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_linked_manual_installment_number_can_be_corrected_without_recreating_other_expenses(): void
    {
        [$user, $anchor, $category] = $this->fixture();
        $this->actingAs($user)->post(route('pluggy.transactions.manual-installment', $anchor), ['date' => '2026-08-05', 'amount' => '24.11', 'number' => 1, 'category_id' => $category->id])->assertSessionHasNoErrors();
        $expense = Expense::where('pluggy_installment_source_id', $anchor->id)->sole();
        $this->withoutVite()->get(route('expenses.edit', $expense))->assertSee('Número desta parcela')->assertSee('name="current_installment"', false);
        $payload = ['description' => 'Compra', 'date' => '2026-08-05', 'amount' => '24.11', 'category_id' => $category->id, 'type' => 'expense', 'payment_mode' => 'cartao', 'bank_choice' => 'unknown', 'current_installment' => 2];
        $this->put(route('expenses.update', $expense), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'pluggy_installment_source_id' => $anchor->id, 'current_installment' => 2, 'total_installments' => 8]);
        $this->assertDatabaseCount('expenses', 1);
        $payload['current_installment'] = 9;
        $this->put(route('expenses.update', $expense), $payload)->assertSessionHasErrors('current_installment');
        $payload['current_installment'] = 0;
        $this->put(route('expenses.update', $expense), $payload)->assertSessionHasErrors('current_installment');
        $this->post(route('pluggy.transactions.manual-installment', $anchor), ['date' => '2026-09-05', 'amount' => '24.06', 'number' => 3, 'category_id' => $category->id])->assertSessionHasNoErrors();
        $payload['current_installment'] = 3;
        $this->put(route('expenses.update', $expense), $payload)->assertSessionHasErrors('current_installment');
        $this->assertSame(2, (int) $expense->fresh()->current_installment);
    }

    public function test_possible_duplicate_is_only_a_warning_and_user_can_remove_imported_copy(): void
    {
        [$user, $anchor, $category] = $this->fixture();
        $copy = $anchor->replicate();
        $copy->fill(['pluggy_transaction_id' => (string) Str::uuid(), 'status' => 'PENDING', 'transaction_date' => '2026-10-31']);
        $copy->save();

        $this->actingAs($user)->withoutVite()->get(route('pluggy.transactions.show', $anchor))->assertOk()->assertSee('Possível parcela duplicada')->assertSee('Ver outra transação');
        $this->get(route('month.show', [2026, 11]))->assertSee('Possível parcela duplicada');
        $this->assertEquals(24.06, app(MonthlyBalanceService::class)->calculate(2026, 10, $user->id)['expenses']);
        $this->assertEquals(24.06, app(MonthlyBalanceService::class)->calculate(2026, 11, $user->id)['expenses']);
        $this->delete(route('pluggy.transactions.destroy', $copy))->assertRedirect();
        $this->assertEquals(0, app(MonthlyBalanceService::class)->calculate(2026, 10, $user->id)['expenses']);
    }

    public function test_manual_installment_is_linked_editable_and_not_created_for_another_user(): void
    {
        [$user, $anchor, $category] = $this->fixture();
        $payload = ['date' => '2026-08-05', 'amount' => '24.11', 'number' => 1, 'category_id' => $category->id];
        $this->actingAs(User::factory()->create())->post(route('pluggy.transactions.manual-installment', $anchor), $payload)->assertNotFound();
        $this->actingAs($user)->post(route('pluggy.transactions.manual-installment', $anchor), $payload)->assertSessionHasNoErrors();
        $expense = Expense::where('pluggy_installment_source_id', $anchor->id)->sole();
        $this->assertEquals(24.11, app(MonthlyBalanceService::class)->calculate(2026, 8, $user->id)['expenses']);
        $this->withoutVite()->get(route('expenses.show', $expense))->assertSee('Ver transação importada');
        $this->get(route('expenses.edit', $expense))->assertDontSee('Compra parcelada?');
        $this->put(route('expenses.update', $expense), ['description' => 'Parcela corrigida', 'date' => '2026-08-05', 'amount' => '24.11', 'category_id' => $category->id, 'type' => 'expense', 'payment_mode' => 'cartao', 'bank_choice' => 'unknown'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'pluggy_installment_source_id' => $anchor->id, 'current_installment' => 1]);
        $this->assertDatabaseCount('expenses', 1);
        $this->post(route('pluggy.transactions.manual-installment', $anchor), $payload)->assertSessionHasErrors('number');
        $imported = $anchor->replicate();
        $imported->fill(['pluggy_transaction_id' => (string) Str::uuid(), 'amount' => '24.11', 'current_installment' => 1, 'transaction_date' => '2026-08-05']);
        $imported->save();
        $this->get(route('pluggy.transactions.show', $imported))->assertSee('Possível parcela manual duplicada')->assertSee('Ver parcela manual');
        $this->get(route('month.show', [2026, 8]))->assertSee('Possível parcela duplicada');
        $this->assertEquals(48.22, app(MonthlyBalanceService::class)->calculate(2026, 8, $user->id)['expenses']);
        $this->delete(route('expenses.destroy', $expense))->assertRedirect();
        $this->assertEquals(24.11, app(MonthlyBalanceService::class)->calculate(2026, 8, $user->id)['expenses']);
    }

    private function fixture(): array
    {
        $user = User::factory()->create();
        $item = PluggyItem::factory()->create(['user_id' => $user->id]);
        $account = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Cartão', 'type' => 'CREDIT']);
        $category = Category::create(['user_id' => $user->id, 'name' => 'Compras', 'type' => 'expense']);
        $transaction = PluggyTransaction::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => $account->id, 'pluggy_transaction_id' => (string) Str::uuid(), 'description' => 'Compra teste', 'amount' => 24.06, 'classification' => 'expense', 'affects_totals' => true, 'direction' => 'DEBIT', 'is_installment' => true, 'current_installment' => 4, 'total_installments' => 8, 'purchase_date' => '2026-07-11', 'status' => 'POSTED', 'transaction_date' => '2026-11-05']);
        return [$user, $transaction, $category];
    }
}
