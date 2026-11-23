<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ManualBank;
use App\Models\User;
use App\Services\MonthlyBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualBankPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Category $category, array $overrides = []): array
    {
        return array_merge(['category_id' => $category->id, 'date' => '2027-01-31', 'type' => $category->type,
            'amount' => '10.01', 'payment_mode' => 'pix', 'bank_choice' => 'none'], $overrides);
    }

    public function test_income_and_expense_can_use_manual_banks_and_payment_modes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $income = Category::create(['user_id' => $user->id, 'name' => 'Receita', 'type' => 'income']);
        $expense = Category::create(['user_id' => $user->id, 'name' => 'Despesa', 'type' => 'expense']);
        $this->post(route('expenses.store'), $this->payload($income, ['bank_choice' => 'new', 'new_bank' => 'Banco Exemplo']))->assertSessionHasNoErrors();
        $bank = ManualBank::where('user_id', $user->id)->firstOrFail();
        foreach (['pix', 'dinheiro', 'debito', 'credito'] as $mode) {
            $this->post(route('expenses.store'), $this->payload($expense, ['bank_choice' => (string) $bank->id, 'payment_mode' => $mode]))->assertSessionHasNoErrors();
            $this->assertDatabaseHas('expenses', ['user_id' => $user->id, 'manual_bank_id' => $bank->id, 'bank_status' => 'bank', 'payment_mode' => $mode]);
        }
        $this->post(route('expenses.store'), $this->payload($income))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('expenses', ['user_id' => $user->id, 'type' => 'income', 'manual_bank_id' => null, 'bank_status' => 'none']);
        $incomeEntry = Expense::where('type', 'income')->where('bank_status', 'bank')->firstOrFail();
        $this->put(route('expenses.update', $incomeEntry), $this->payload($income, ['bank_choice' => 'none']))->assertSessionHasNoErrors();
        $this->assertNull($incomeEntry->fresh()->manual_bank_id);
        $this->put(route('expenses.update', $incomeEntry), $this->payload($income, ['bank_choice' => (string) $bank->id]))->assertSessionHasNoErrors();
        $this->assertSame($bank->id, $incomeEntry->fresh()->manual_bank_id);
        $totals = app(MonthlyBalanceService::class)->calculate(2027, 1);
        $this->assertEqualsWithDelta(30.03, $totals['expenses'], 0.001);
        $this->assertEqualsWithDelta(10.01, $totals['expensesPix'], 0.001);
        $this->withoutVite()->get(route('month.show', [2027, 1]).'?payment_mode=debito')->assertOk()->assertSee('Cartão de débito');
    }

    public function test_validation_and_ownership_and_legacy_data(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['user_id' => $user->id, 'name' => 'Despesa', 'type' => 'expense']);
        $bank = ManualBank::create(['user_id' => $other->id, 'name' => 'Privado']);
        $this->post(route('expenses.store'), $this->payload($category, ['bank_choice' => (string) $bank->id]))->assertSessionHasErrors('bank_choice');
        $this->post(route('expenses.store'), $this->payload($category, ['bank_choice' => null]))->assertSessionHasErrors('bank_choice');
        $this->post(route('expenses.store'), $this->payload($category, ['bank_choice' => 'unknown']))->assertSessionHasErrors('bank_choice');
        $this->post(route('expenses.store'), $this->payload($category, ['payment_mode' => 'cartao']))->assertSessionHasErrors('payment_mode');
        $this->post(route('expenses.store'), $this->payload($category, ['payment_mode' => 'invalid']))->assertSessionHasErrors('payment_mode');
        $legacy = Expense::create(['user_id' => $user->id, 'category_id' => $category->id, 'date' => '2027-01-31', 'month' => 1, 'year' => 2027,
            'type' => 'expense', 'amount' => '5.00', 'payment_mode' => 'cartao']);
        $this->withoutVite()->get(route('expenses.show', $legacy))->assertOk()->assertSee('Banco não informado')->assertSee('Cartão — modalidade não informada');
        $this->put(route('expenses.update', $legacy), $this->payload($category, ['bank_choice' => 'unknown', 'payment_mode' => 'cartao']))->assertSessionHasNoErrors();
        $this->assertNull($legacy->fresh()->bank_status);
        $this->assertSame('cartao', $legacy->fresh()->payment_mode);
    }

    public function test_group_inherits_bank_single_edit_cannot_change_it_and_group_edit_can(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $category = Category::create(['user_id' => $user->id, 'name' => 'Despesa', 'type' => 'expense']);
        $bank = ManualBank::create(['user_id' => $user->id, 'name' => 'Banco A']);
        $otherBank = ManualBank::create(['user_id' => $user->id, 'name' => 'Banco B']);
        $payload = $this->payload($category, ['bank_choice' => (string) $bank->id, 'payment_mode' => 'credito', 'is_installment' => '1', 'total_installments' => 3]);
        $this->post(route('expenses.store'), $payload)->assertSessionHasNoErrors();
        $items = Expense::where('user_id', $user->id)->orderBy('current_installment')->get();
        $this->assertSame([$bank->id, $bank->id, $bank->id], $items->pluck('manual_bank_id')->all());
        try {
            $bank->delete();
            $this->fail('Um banco vinculado não deve ser excluído.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertDatabaseHas('manual_banks', ['id' => $bank->id]);
        }
        $this->withoutVite()->get(route('expenses.edit', $items[1]))->assertOk()->assertSee('id="bankControls" class="hidden"', false);
        $this->put(route('expenses.update', $items[1]), array_merge($payload, ['installment_scope' => 'single', 'bank_choice' => (string) $otherBank->id, 'payment_mode' => 'debito']))->assertSessionHasNoErrors();
        $this->assertSame($bank->id, $items[1]->fresh()->manual_bank_id);
        $this->assertSame('debito', $items[1]->fresh()->payment_mode);
        $this->put(route('expenses.update', $items[0]), array_merge($payload, ['installment_scope' => 'group', 'bank_choice' => (string) $otherBank->id]))->assertSessionHasNoErrors();
        $this->assertSame([$otherBank->id, $otherBank->id, $otherBank->id], Expense::orderBy('current_installment')->pluck('manual_bank_id')->all());
    }

    public function test_migration_preserves_legacy_records_and_blocks_destructive_rollback(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Despesa', 'type' => 'expense']);
        $legacy = Expense::create(['user_id' => $user->id, 'category_id' => $category->id, 'date' => '2027-01-31', 'month' => 1, 'year' => 2027,
            'type' => 'expense', 'amount' => '5.00', 'payment_mode' => 'cartao']);
        $pixLegacy = Expense::create(['user_id' => $user->id, 'category_id' => $category->id, 'date' => '2027-01-31', 'month' => 1, 'year' => 2027,
            'type' => 'expense', 'amount' => '1.00', 'payment_mode' => 'pix']);
        $banksMigration = require database_path('migrations/2026_10_22_000000_create_manual_banks_table.php');
        $expenseMigration = require database_path('migrations/2026_10_22_000001_add_bank_and_payment_fields_to_expenses.php');
        $expenseMigration->down();
        $banksMigration->down();
        $this->assertDatabaseHas('expenses', ['id' => $legacy->id, 'payment_mode' => 'cartao', 'amount' => 5]);
        $banksMigration->up();
        $expenseMigration->up();
        $this->assertDatabaseHas('expenses', ['id' => $legacy->id, 'payment_mode' => 'cartao', 'bank_status' => null, 'manual_bank_id' => null]);
        $this->assertDatabaseHas('expenses', ['id' => $pixLegacy->id, 'payment_mode' => 'pix', 'bank_status' => null, 'manual_bank_id' => null]);
        $legacy->update(['bank_status' => 'none', 'payment_mode' => 'credito']);
        try {
            $expenseMigration->down();
            $this->fail('A reversão com novos dados de lançamentos deveria ser bloqueada.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Reversão bloqueada', $exception->getMessage());
            $this->assertDatabaseHas('expenses', ['id' => $legacy->id, 'payment_mode' => 'credito', 'bank_status' => 'none']);
        }
        ManualBank::create(['user_id' => $user->id, 'name' => 'Banco de teste']);
        try {
            $banksMigration->down();
            $this->fail('A reversão com dados de banco deveria ser bloqueada.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Reversão bloqueada', $exception->getMessage());
            $this->assertDatabaseHas('expenses', ['id' => $legacy->id, 'bank_status' => 'none']);
        }
    }
}
