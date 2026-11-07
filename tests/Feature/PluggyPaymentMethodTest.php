<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use App\Services\FinancialSummaryService;
use App\Services\Pluggy\PluggyTransactionImporter;
use App\Services\Pluggy\PluggyTransactionRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluggyPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private function imported(): array
    {
        $user = User::factory()->create();
        $connection = PluggyItem::factory()->create(['user_id' => $user->id]);
        $account = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $connection->id,
            'pluggy_account_id' => 'account-test', 'name' => 'Conta', 'type' => 'BANK', 'currency_code' => 'BRL', 'balance' => 0]);
        $payload = ['id' => 'payment-test', 'description' => 'Compra', 'date' => '2026-09-10',
            'amount' => -100, 'type' => 'DEBIT', 'status' => 'POSTED', 'operationType' => 'CARTAO'];
        app(PluggyTransactionImporter::class)->import($connection, $account, [$payload]);

        return [$user, $connection, $account, PluggyTransaction::where('pluggy_transaction_id', 'payment-test')->firstOrFail(), $payload];
    }

    public function test_manual_choice_survives_import_and_rule_reclassification_and_can_be_reset(): void
    {
        [$user, $connection, $account, $transaction, $payload] = $this->imported();
        $values = ['description' => 'Compra', 'transaction_date' => '2026-09-10', 'category_id' => null, 'payment_method' => 'pix'];
        $this->actingAs($user)->put(route('pluggy.transactions.update', $transaction), $values)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('pix', $transaction->fresh()->payment_method);
        $this->assertTrue($transaction->fresh()->user_modified_payment_method);
        $totals = app(FinancialSummaryService::class)->importedMonthTotals($user->id, 2026, 9);
        $this->assertEquals(100, $totals['pix_expenses']);
        $this->assertEquals(0, $totals['expenses']);

        app(PluggyTransactionImporter::class)->import($connection, $account, [$payload]);
        app(PluggyTransactionRuleService::class)->reclassifyForUser($user->id);
        $this->assertSame('pix', $transaction->fresh()->payment_method);
        $this->assertTrue($transaction->fresh()->user_modified_payment_method);

        $values['restore_automatic'] = '1';
        $this->actingAs($user)->put(route('pluggy.transactions.update', $transaction), $values)->assertSessionHasNoErrors();
        $this->assertSame('card', $transaction->fresh()->payment_method);
        $this->assertFalse($transaction->fresh()->user_modified_payment_method);
        $totals = app(FinancialSummaryService::class)->importedMonthTotals($user->id, 2026, 9);
        $this->assertEquals(0, $totals['pix_expenses']);
        $this->assertEquals(100, $totals['expenses']);
    }

    public function test_keep_does_not_reclassify_old_records_and_invalid_or_other_users_cannot_edit(): void
    {
        [$user, , , $transaction] = $this->imported();
        $transaction->update(['payment_method' => 'pix']);
        $values = ['description' => 'Compra', 'transaction_date' => '2026-09-10', 'category_id' => null, 'payment_method' => 'keep'];
        $this->actingAs($user)->put(route('pluggy.transactions.update', $transaction), $values)->assertSessionHasNoErrors();
        $this->assertSame('pix', $transaction->fresh()->payment_method);
        $values['payment_method'] = 'invalid';
        $this->actingAs($user)->put(route('pluggy.transactions.update', $transaction), $values)->assertSessionHasErrors('payment_method');
        $values['payment_method'] = 'debito';
        $this->actingAs(User::factory()->create())->put(route('pluggy.transactions.update', $transaction), $values)->assertNotFound();
        $this->assertSame('pix', $transaction->fresh()->payment_method);
    }

    public function test_current_method_is_selected_and_saving_it_preserves_its_source(): void
    {
        [$user, , , $transaction] = $this->imported();
        $transaction->update(['payment_method' => 'pix']);
        $this->actingAs($user)->get(route('pluggy.transactions.edit', $transaction))
            ->assertOk()->assertSee('value="pix" selected', false)
            ->assertDontSee('Manter atual:')->assertDontSee('Recalcular automaticamente');

        $values = ['description' => 'Compra', 'transaction_date' => '2026-09-10', 'category_id' => null, 'payment_method' => 'pix'];
        $this->put(route('pluggy.transactions.update', $transaction), $values)->assertSessionHasNoErrors();
        $this->assertSame('pix', $transaction->fresh()->payment_method);
        $this->assertFalse($transaction->fresh()->user_modified_payment_method);

        $transaction->update(['user_modified_payment_method' => true]);
        $this->get(route('pluggy.transactions.edit', $transaction))->assertSee('Voltar à classificação automática');
        $this->put(route('pluggy.transactions.update', $transaction), $values)->assertSessionHasNoErrors();
        $this->assertTrue($transaction->fresh()->user_modified_payment_method);
    }

    public function test_preview_is_read_only_and_skips_manual_choices(): void
    {
        [$user, , , $transaction] = $this->imported();
        $transaction->update(['payment_method' => 'pix']);
        $this->artisan('pluggy:preview-payment-methods', ['user' => $user->id])
            ->expectsOutputToContain('pix -> card: 1')->assertSuccessful();
        $this->assertSame('pix', $transaction->fresh()->payment_method);
        $transaction->update(['user_modified_payment_method' => true]);
        $this->artisan('pluggy:preview-payment-methods', ['user' => $user->id])
            ->expectsOutputToContain('escolhas manuais preservadas: 1')->assertSuccessful();
        $this->assertSame('pix', $transaction->fresh()->payment_method);
    }

    public function test_explicit_debit_choice_is_visible_in_matching_filter_only(): void
    {
        [$user, , , $transaction] = $this->imported();
        $values = ['description' => 'Compra', 'transaction_date' => '2026-09-10', 'category_id' => null, 'payment_method' => 'debito'];
        $this->actingAs($user)->put(route('pluggy.transactions.update', $transaction), $values)->assertSessionHasNoErrors();
        $this->get(route('month.show', [2026, 9]).'?payment_mode=debito')->assertOk()
            ->assertViewHas('typeExpenses', fn ($expenses) => $expenses->contains('id', $transaction->id));
        $this->get(route('month.show', [2026, 9]).'?payment_mode=pix')->assertOk()
            ->assertViewHas('typeExpenses', fn ($expenses) => ! $expenses->contains('id', $transaction->id));
        $this->assertSame('debito', $transaction->fresh()->payment_mode);
    }

    public function test_manual_and_imported_transactions_share_views_and_pluggy_can_create_a_category(): void
    {
        [$user, , , $transaction] = $this->imported();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Moradia', 'type' => 'expense']);
        $expense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'date' => '2026-09-10',
            'month' => 9,
            'year' => 2026,
            'type' => 'expense',
            'description' => 'Manual',
            'amount' => 80,
            'payment_mode' => 'pix',
            'bank_status' => 'none',
            'is_installment' => false,
        ]);

        $this->actingAs($user)->withoutVite()->get(route('expenses.show', $expense))->assertOk()->assertViewIs('transactions.show');
        $this->get(route('expenses.edit', $expense))->assertOk()->assertViewIs('transactions.edit');
        $this->get(route('pluggy.transactions.show', $transaction))->assertOk()->assertViewIs('transactions.show');
        $this->get(route('pluggy.transactions.edit', $transaction))->assertOk()->assertViewIs('transactions.edit')->assertSee('+ Criar nova categoria');

        $this->put(route('pluggy.transactions.update', $transaction), [
            'description' => 'Compra ajustada',
            'transaction_date' => '2026-09-10',
            'category_id' => 'new',
            'new_category' => 'Assinaturas',
            'payment_method' => 'keep',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $newCategory = Category::query()->where('user_id', $user->id)->where('name', 'Assinaturas')->where('type', 'expense')->sole();
        $this->assertSame($newCategory->id, $transaction->fresh()->category_id);
    }

    public function test_month_list_shows_chosen_category_and_institution_for_imported_movements(): void
    {
        [$user, $connection, $account, $transaction] = $this->imported();
        $connection->update(['connector_name' => 'MyPluggy', 'display_name' => 'Instituição Exemplo']);
        $category = Category::create(['user_id' => $user->id, 'name' => 'Categoria escolhida', 'type' => 'expense']);
        $transaction->update(['category_id' => $category->id, 'category_source' => 'user', 'pluggy_category_name' => 'Categoria original']);

        $this->actingAs($user)->withoutVite()->get(route('month.show', [2026, 9]))
            ->assertOk()->assertSee('Categoria escolhida')->assertSee('Instituição Exemplo')
            ->assertDontSee('Categoria original')->assertDontSee('>Banco</span>', false);
    }
}
