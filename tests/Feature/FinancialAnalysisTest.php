<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Group;
use App\Models\MonthlyBalance;
use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinancialAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_six_charts_live_on_a_separate_authenticated_page_linked_from_finances(): void
    {
        $this->get(route('analyses.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $response = $this->get(route('analyses.index', ['year' => 2026]));
        $response->assertOk()->assertViewIs('analyses.index')->assertSeeInOrder([
            'Receitas × despesas', 'Receitas e despesas por grupo', 'Receitas e despesas por categoria',
            'Evolução do saldo', 'Maiores despesas', 'Despesas por forma de pagamento',
        ])->assertDontSee('annual-chart-description');
        $this->assertSame(6, substr_count($response->getContent(), 'data-analysis-chart='));
        $this->get(route('finances.index', ['year' => 2026]))->assertOk()
            ->assertSee(route('analyses.index', ['year' => 2026]), false)
            ->assertSee('Total anual de gastos')->assertDontSee('data-analysis-chart=')->assertSee('annual-chart-description')->assertDontSee('Despesas por forma de pagamento');
    }

    public function test_all_charts_agree_on_net_movements_and_daily_accumulated_balance(): void
    {
        [$user, $group, $income, $expense, $bonus, $account] = $this->dataset();
        $response = $this->actingAs($user)->get(route('analyses.index', ['year' => 2026, 'month' => 1]));
        $response->assertOk()->assertSee('Janeiro de 2026')->assertSee('R$ 1.085,77');
        $data = $response->viewData('analysis');
        $this->assertSame(['income' => 120031, 'expense' => 11454, 'balance' => 108577], $data['totals']);
        $this->assertSame('114.54', $data['payments']['total']);
        $this->assertCount(31, $data['periods']);
        $this->assertSame(100011, $data['periods'][4]['income']);
        $this->assertSame(10003, $data['periods'][9]['expense']);
        $this->assertSame(108577, $data['periods'][30]['balance']);
        $groups = array_column($data['groups'], null, 'key');
        $this->assertSame(100011, $groups['group:'.$group->id]['income']);
        $this->assertSame(11454, $groups['group:'.$group->id]['expense']);
        $this->assertSame(20020, $groups['none']['income']);
        $this->assertCount(3, $data['categories']);
        $this->assertConsistent($data);

        $annual = $this->get(route('analyses.index', ['year' => 2026]))->assertOk()->viewData('analysis');
        $this->assertSame(['income' => 160031, 'expense' => 11756, 'balance' => 148275], $annual['totals']);
        $this->assertCount(12, $annual['periods']);
        $this->assertSame(108577, $annual['periods'][0]['balance']);
        $this->assertSame(148275, $annual['periods'][1]['balance']);
        $this->assertSame(148275, $annual['periods'][11]['balance']);
        $this->assertConsistent($annual);
    }

    public function test_income_categories_and_shared_filters_update_every_chart(): void
    {
        [$user, $group, $income, $expense, $bonus, $account] = $this->dataset();
        $this->actingAs($user);
        foreach ([
            [['month' => 1, 'group_id' => $group->id], 100011, 11454],
            [['month' => 1, 'group_id' => $group->id, 'category_id' => $income->id], 100011, 0],
            [['month' => 1, 'category_id' => $expense->id], 0, 11454],
            [['month' => 1, 'group_id' => 'none'], 20020, 0],
            [['month' => 1, 'source' => 'manual'], 100011, 10003],
            [['month' => 1, 'source' => 'imported', 'bank' => 'account:'.$account->id], 20020, 1451],
        ] as [$filters, $expectedIncome, $expectedExpense]) {
            $response = $this->get(route('analyses.index', ['year' => 2026, ...$filters]))->assertOk();
            $data = $response->viewData('analysis');
            $this->assertSame($expectedIncome, $data['totals']['income']);
            $this->assertSame($expectedExpense, $data['totals']['expense']);
            $this->assertConsistent($data);
        }
        $response->assertViewHas('options', fn ($options) => $options['categories']->contains('id', $income->id));
    }

    public function test_categories_with_equal_names_are_distinct_and_uncategorized_imports_are_retained(): void
    {
        $user = User::factory()->create();
        $income = Category::create(['user_id' => $user->id, 'name' => 'Outros', 'type' => 'income']);
        $expense = Category::create(['user_id' => $user->id, 'name' => 'Outros', 'type' => 'expense']);
        $this->manual($user, $income, '50.00', '2026-01-02');
        $this->manual($user, $expense, '10.00', '2026-01-03');
        $this->imported($this->account($user), '5.00', ['category_id' => null]);
        $data = $this->actingAs($user)->get(route('analyses.index', ['year' => 2026]))->assertOk()->viewData('analysis');
        $categories = array_column($data['categories'], null, 'key');
        $this->assertCount(3, $categories);
        $this->assertSame(5000, $categories['category:'.$income->id]['income']);
        $this->assertSame(1000, $categories['category:'.$expense->id]['expense']);
        $this->assertSame(500, $categories['none:expense']['expense']);
        $this->assertSame('Sem categoria', $categories['none:expense']['label']);
        $this->assertSame('Sem grupo', $data['groups'][0]['label']);
        $this->assertConsistent($data);
    }

    public function test_largest_expenses_are_individual_positive_expenses_limited_to_ten(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Compras', 'type' => 'expense']);
        foreach (range(1, 12) as $number) {
            $this->manual($user, $category, (string) ($number * 10), '2026-01-10');
        }
        $account = $this->account($user);
        $this->imported($account, '9000.00', ['classification' => 'income']);
        $this->imported($account, '1000.00', ['classification' => 'refund']);
        $response = $this->actingAs($user)->get(route('analyses.index', ['year' => 2026]))->assertOk();
        $largest = $response->viewData('analysis')['largest'];
        $this->assertCount(10, $largest);
        $this->assertSame(range(12000, 3000, -1000), array_column($largest, 'cents'));
        $response->assertSee(route('expenses.show', $largest[0]['id']), false);
    }

    public function test_leap_day_zero_activity_and_negative_totals_are_supported(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $empty = $this->get(route('analyses.index', ['year' => 2028, 'month' => 2]))->assertOk()->viewData('analysis');
        $this->assertCount(29, $empty['periods']);
        $this->assertFalse($empty['has_movements']);
        $account = $this->account($user);
        $this->imported($account, '10.00', ['transaction_date' => '2028-02-29']);
        $this->imported($account, '15.00', ['transaction_date' => '2028-02-29', 'classification' => 'refund']);
        $response = $this->get(route('analyses.index', ['year' => 2028, 'month' => 2]))->assertOk()->assertSee('Crédito líquido')->assertSee('R$ -5,00');
        $data = $response->viewData('analysis');
        $this->assertSame(['income' => 0, 'expense' => -500, 'balance' => 500], $data['totals']);
        $this->assertSame('29/02/2028', $data['periods'][28]['full_label']);
        $this->assertSame(500, $data['periods'][28]['balance']);
        $this->assertConsistent($data);
    }

    public function test_first_and_last_dates_of_year_are_included_without_crossing_the_period(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Compras', 'type' => 'expense']);
        $this->manual($user, $category, '1.01', '2026-01-01');
        $this->manual($user, $category, '2.02', '2026-12-31');
        $this->manual($user, $category, '999.00', '2025-12-31');
        $this->manual($user, $category, '999.00', '2027-01-01');
        $account = $this->account($user);
        $this->imported($account, '3.03', ['transaction_date' => '2026-01-01']);
        $this->imported($account, '4.04', ['transaction_date' => '2026-12-31']);
        $this->imported($account, '999.00', ['transaction_date' => '2027-01-01']);
        $data = $this->actingAs($user)->get(route('analyses.index', ['year' => 2026]))->assertOk()->viewData('analysis');
        $this->assertSame(1010, $data['totals']['expense']);
        $this->assertSame(404, $data['periods'][0]['expense']);
        $this->assertSame(606, $data['periods'][11]['expense']);
        $this->assertConsistent($data);
    }

    private function assertConsistent(array $data): void
    {
        foreach (['groups', 'categories', 'periods'] as $section) {
            $this->assertSame($data['totals']['income'], array_sum(array_column($data[$section], 'income')));
            $this->assertSame($data['totals']['expense'], array_sum(array_column($data[$section], 'expense')));
        }
        $this->assertSame($data['totals']['balance'], $data['periods'][array_key_last($data['periods'])]['balance']);
        $this->assertSame($data['totals']['expense'], array_sum(array_column($data['payments']['rows'], 'cents')));
    }

    private function dataset(): array
    {
        $user = User::factory()->create();
        $group = Group::create(['user_id' => $user->id, 'name' => 'Essenciais']);
        $income = Category::create(['user_id' => $user->id, 'name' => 'Salário', 'type' => 'income', 'group_id' => $group->id]);
        $expense = Category::create(['user_id' => $user->id, 'name' => 'Alimentação', 'type' => 'expense', 'group_id' => $group->id]);
        $bonus = Category::create(['user_id' => $user->id, 'name' => 'Extras', 'type' => 'income']);
        $account = $this->account($user);
        $this->manual($user, $income, '1000.11', '2026-01-05');
        $this->manual($user, $expense, '100.03', '2026-01-10');
        $this->manual($user, $income, '400.00', '2026-02-01');
        $this->manual($user, $expense, '3.02', '2026-02-01');
        $this->imported($account, '200.20', ['classification' => 'income', 'category_id' => $bonus->id, 'transaction_date' => '2026-01-15']);
        $this->imported($account, '20.02', ['category_id' => $expense->id, 'transaction_date' => '2026-01-16']);
        $this->imported($account, '5.01', ['category_id' => $expense->id, 'classification' => 'refund', 'transaction_date' => '2026-01-17']);
        $this->imported($account, '0.50', ['category_id' => $expense->id, 'classification' => 'cashback', 'transaction_date' => '2026-01-18']);
        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2025, 'month' => 12, 'closing_balance' => '900.99']);

        return [$user, $group, $income, $expense, $bonus, $account];
    }

    private function manual(User $user, Category $category, string $amount, string $date): Expense
    {
        return Expense::create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => $category->type,
            'amount' => $amount, 'date' => $date, 'year' => (int) substr($date, 0, 4), 'month' => (int) substr($date, 5, 2),
            'description' => $category->name, 'payment_mode' => 'pix',
        ]);
    }

    private function account(User $user): PluggyAccount
    {
        $connection = PluggyItem::create(['user_id' => $user->id, 'pluggy_item_id' => (string) Str::uuid(), 'last_event' => 'item/created']);

        return PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $connection->id,
            'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Conta', 'type' => 'BANK']);
    }

    private function imported(PluggyAccount $account, string $amount, array $extra = []): PluggyTransaction
    {
        return PluggyTransaction::create(array_merge([
            'user_id' => $account->user_id, 'pluggy_connection_id' => $account->pluggy_connection_id,
            'pluggy_account_id' => $account->id, 'pluggy_transaction_id' => (string) Str::uuid(), 'description' => 'Compra',
            'transaction_date' => '2026-01-10', 'amount' => $amount, 'classification' => 'expense',
            'payment_method' => 'pix', 'status' => 'POSTED', 'affects_totals' => true,
        ], $extra));
    }
}
