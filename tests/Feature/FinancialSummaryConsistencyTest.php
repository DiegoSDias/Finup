<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\MonthlyBalance;
use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use App\Services\HomeService;
use App\Services\MonthlyBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Services\Pluggy\PluggyTransactionImporter;
use Tests\TestCase;

class FinancialSummaryConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_january_annual_dashboard_includes_prior_december_balance(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2025, 'month' => 12, 'closing_balance' => '100.11']);

        $monthly = app(MonthlyBalanceService::class)->calculate(2026, 1);
        $annual = app(HomeService::class)->index(2026);

        $this->assertEqualsWithDelta(100.11, $monthly['incomes'], 0.00001);
        $this->assertEqualsWithDelta($monthly['incomes'], $annual['finalIncomeBalance'][1], 0.00001);
    }

    public function test_monthly_and_annual_views_agree_with_manual_and_imported_movements(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);
        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2025, 'month' => 12, 'closing_balance' => '100.11']);
        MonthlyBalance::create(['user_id' => $other->id, 'year' => 2025, 'month' => 12, 'closing_balance' => '999.99']);
        $income = Category::create(['user_id' => $user->id, 'name' => 'Receitas', 'type' => 'income']);
        $expense = Category::create(['user_id' => $user->id, 'name' => 'Despesas', 'type' => 'expense']);
        foreach ([
            [$income, 'income', '120.12', null],
            [$expense, 'expense', '10.01', 'cartao'],
            [$expense, 'expense', '5.02', 'pix'],
            [$expense, 'expense', '1.03', null],
        ] as [$category, $type, $amount, $mode]) {
            Expense::create(['user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-01-10',
                'year' => 2026, 'month' => 1, 'type' => $type, 'amount' => $amount, 'payment_mode' => $mode]);
        }

        $connection = PluggyItem::create(['user_id' => $user->id, 'pluggy_item_id' => (string) Str::uuid(), 'last_event' => 'item/created']);
        $account = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $connection->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Teste', 'type' => 'BANK']);
        foreach ([
            ['income', '20.20', 'other', true],
            ['expense', '7.07', 'other', true],
            ['expense', '2.02', 'pix', true],
            ['expense', '3.03', null, true],
            ['refund', '1.01', 'other', true],
            ['cashback', '0.50', 'pix', true],
            ['internal_transfer', '50.00', 'pix', false],
        ] as [$classification, $amount, $method, $affects]) {
            PluggyTransaction::create(['user_id' => $user->id, 'pluggy_connection_id' => $connection->id,
                'pluggy_account_id' => $account->id, 'pluggy_transaction_id' => (string) Str::uuid(),
                'description' => 'Teste', 'transaction_date' => '2026-01-10', 'amount' => $amount,
                'classification' => $classification, 'payment_method' => $method, 'status' => 'POSTED', 'affects_totals' => $affects]);
        }

        $monthly = app(MonthlyBalanceService::class)->calculate(2026, 1);
        $annual = app(HomeService::class)->index(2026);

        $this->assertEqualsWithDelta(100.11, $monthly['balanceMonthPrevious'], 0.00001);
        $this->assertEqualsWithDelta(240.43, $monthly['incomes'], 0.00001);
        $this->assertEqualsWithDelta(20.13, $monthly['expenses'], 0.00001);
        $this->assertEqualsWithDelta(6.54, $monthly['expensesPix'], 0.00001);
        $this->assertEqualsWithDelta(213.76, $monthly['balanceAvailable'], 0.00001);
        $this->assertEqualsWithDelta($monthly['incomes'], $annual['finalIncomeBalance'][1], 0.00001);
        $this->assertEqualsWithDelta($monthly['expenses'] + $monthly['expensesPix'], $annual['expenseBalance'][1], 0.00001);
    }

    public function test_import_updates_existing_closed_balances_in_subsequent_months(): void
    {
        $user = User::factory()->create();
        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2026, 'month' => 1, 'closing_balance' => '0.00']);
        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2026, 'month' => 2, 'closing_balance' => '0.00']);
        $connection = PluggyItem::create(['user_id' => $user->id, 'pluggy_item_id' => (string) Str::uuid(), 'last_event' => 'item/created']);
        $account = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $connection->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Teste', 'type' => 'BANK']);
        app(PluggyTransactionImporter::class)->import($connection, $account, [[
            'id' => (string) Str::uuid(), 'description' => 'Teste', 'date' => '2026-01-10',
            'amount' => '0.01', 'type' => 'DEBIT', 'status' => 'POSTED', 'currencyCode' => 'BRL',
        ]]);
        $this->assertEquals(-0.01, MonthlyBalance::where('user_id', $user->id)->where('year', 2026)->where('month', 1)->value('closing_balance'));
        $this->assertEquals(-0.01, MonthlyBalance::where('user_id', $user->id)->where('year', 2026)->where('month', 2)->value('closing_balance'));
    }
}
