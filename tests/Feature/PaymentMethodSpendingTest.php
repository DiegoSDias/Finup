<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Group;
use App\Models\ManualBank;
use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentMethodSpendingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_chart_ranks_net_spending_and_agrees_with_the_annual_summary(): void
    {
        $user = User::factory()->create();
        $category = $this->category($user);
        $account = $this->account($user);
        foreach ([['pix', '100.01'], ['credito', '40.02'], ['debito', '30.00'], ['dinheiro', '10.00'], ['cartao', '7.25'], [null, '3.50']] as [$method, $amount]) {
            $this->manual($user, $category, $amount, ['payment_mode' => $method]);
        }
        $this->imported($account, '20.03', ['category_id' => $category->id]);
        $this->imported($account, '5.01', ['classification' => 'refund']);
        $this->imported($account, '2.00', ['classification' => 'fee', 'payment_method' => 'debito']);
        $this->imported($account, '1.00', ['classification' => 'cashback', 'payment_method' => 'debito']);
        $this->imported($account, '2.75', ['payment_method' => 'card']);
        $this->imported($account, '1.50', ['payment_method' => 'other']);
        $this->imported($this->account($user, 'CREDIT'), '10.00', ['status' => 'PENDING', 'payment_method' => 'credito']);

        foreach ([
            ['classification' => 'income'],
            ['classification' => 'internal_transfer', 'affects_totals' => false],
            ['classification' => 'credit_card_payment', 'affects_totals' => false],
            ['status' => 'PENDING'],
            ['removed_at' => now()],
            ['deleted_by_user_at' => now()],
        ] as $extra) {
            $this->imported($account, '999.99', $extra);
        }
        $this->manual($user, $category, '999.99', ['recurrence_status' => 'skipped']);
        $this->manual($user, $category, '999.99', ['type' => 'income']);

        $response = $this->actingAs($user)->get(route('analyses.index', ['year' => 2026]));
        $response->assertOk()->assertSee('Despesas por forma de pagamento')
            ->assertSeeInOrder(['data-payment-method="pix"', 'data-payment-method="credito"', 'data-payment-method="debito"'], false)
            ->assertSee('52,0%')->assertSee('Cartão sem modalidade')->assertSee('Outros / não informado');
        $summary = $response->viewData('analysis')['payments'];
        $this->assertSame('221.05', $summary['total']);
        $this->assertSame([
            'pix' => '115.03', 'credito' => '50.02', 'debito' => '31.00',
            'dinheiro' => '10.00', 'card' => '10.00', 'other' => '5.00',
        ], array_column($summary['rows'], 'amount', 'method'));
        $this->assertEquals((float) $summary['total'], app(\App\Services\HomeService::class)->index(2026)['totalExpense']);
    }

    public function test_filters_combine_month_group_category_source_and_bank(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['user_id' => $user->id, 'name' => 'Essenciais']);
        $first = $this->category($user, ['group_id' => $group->id]);
        $second = $this->category($user, ['group_id' => $group->id, 'name' => 'Transporte']);
        $ungrouped = $this->category($user, ['name' => 'Avulsas']);
        $bank = ManualBank::create(['user_id' => $user->id, 'name' => 'Banco manual']);
        $account = $this->account($user);
        $otherAccount = $this->account($user);
        $this->manual($user, $first, '100.00', ['manual_bank_id' => $bank->id, 'bank_status' => 'bank']);
        $this->manual($user, $second, '50.00');
        $this->manual($user, $ungrouped, '25.00', ['bank_status' => 'none']);
        $this->manual($user, $first, '999.00', ['date' => '2026-04-10', 'month' => 4]);
        $this->manual($user, $first, '999.00', ['date' => '2025-03-10', 'year' => 2025]);
        $this->imported($account, '40.00', ['category_id' => $first->id]);
        $this->imported($otherAccount, '10.00', ['category_id' => $second->id]);
        $this->imported($account, '5.00');
        $base = ['year' => 2026, 'month' => 3];
        $this->actingAs($user);

        foreach ([
            [[], '230.00'],
            [['group_id' => $group->id], '200.00'],
            [['group_id' => $group->id, 'category_id' => $first->id], '140.00'],
            [['group_id' => $group->id, 'category_id' => $first->id, 'source' => 'manual', 'bank' => 'manual:'.$bank->id], '100.00'],
            [['group_id' => $group->id, 'category_id' => $first->id, 'source' => 'imported', 'bank' => 'account:'.$account->id], '40.00'],
            [['source' => 'manual'], '175.00'],
            [['source' => 'imported'], '55.00'],
            [['bank' => 'manual:'.$bank->id], '100.00'],
            [['bank' => 'account:'.$account->id], '45.00'],
            [['bank' => 'none'], '25.00'],
            [['bank' => 'unknown'], '50.00'],
            [['group_id' => 'none'], '30.00'],
            [['category_id' => 'none'], '5.00'],
            [['group_id' => 'none', 'category_id' => 'none'], '5.00'],
            [['month' => 4], '999.00'],
            [['year' => 2025], '999.00'],
        ] as [$filters, $expected]) {
            $this->get(route('analyses.index', array_replace($base, $filters)))
                ->assertOk()->assertViewHas('analysis', fn ($analysis) => $analysis['payments']['total'] === $expected);
        }

        $filters = array_replace($base, ['group_id' => $group->id, 'category_id' => $first->id]);
        $response = $this->get(route('analyses.index', $filters));
        $response->assertSee(route('analyses.index', array_replace($filters, ['year' => 2027])))
            ->assertSee(route('analyses.index', ['year' => 2026]), false)
            ->assertViewHas('options', fn ($options) => $options['categories']->pluck('id')->contains($first->id));
    }

    public function test_other_users_movements_and_filter_options_are_isolated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->manual($user, $this->category($user), '12.34');
        $privateGroup = Group::create(['user_id' => $other->id, 'name' => 'Grupo privado']);
        $category = $this->category($other, ['group_id' => $privateGroup->id, 'name' => 'Categoria privada']);
        $this->manual($other, $category, '999.00');
        $account = $this->account($other);
        $this->imported($account, '999.00');
        $bank = ManualBank::create(['user_id' => $other->id, 'name' => 'Banco privado']);

        $response = $this->actingAs($user)->get(route('analyses.index', ['year' => 2026]));
        $response->assertOk()->assertViewHas('analysis', fn ($analysis) => $analysis['payments']['total'] === '12.34')
            ->assertDontSee('Grupo privado')->assertDontSee('Categoria privada')->assertDontSee('Banco privado');
        $this->assertCount(0, $response->viewData('options')['accounts']);

        foreach ([['group_id' => $privateGroup->id], ['category_id' => $category->id], ['bank' => 'manual:'.$bank->id], ['bank' => 'account:'.$account->id]] as $filter) {
            $this->getJson(route('analyses.index', array_merge(['year' => 2026], $filter)))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($filter));
        }
    }

    public function test_invalid_and_incompatible_filters_are_rejected(): void
    {
        $user = User::factory()->create();
        $group = Group::create(['user_id' => $user->id, 'name' => 'Grupo']);
        $category = $this->category($user);

        $bank = ManualBank::create(['user_id' => $user->id, 'name' => 'Banco']);
        $account = $this->account($user);
        $this->actingAs($user);

        foreach ([
            [['year' => 2101], 'year'], [['year' => 'abc'], 'year'], [['year' => 1899], 'year'],
            [['month' => 0], 'month'], [['month' => 13], 'month'], [['month' => 'March'], 'month'],
            [['source' => 'unknown'], 'source'], [['group_id' => 99999], 'group_id'],
            [['group_id' => ['invalid']], 'group_id'], [['category_id' => ['invalid']], 'category_id'],

            [['group_id' => $group->id, 'category_id' => $category->id], 'category_id'],
            [['group_id' => $group->id, 'category_id' => 'none'], 'category_id'],
            [['bank' => 'invalid'], 'bank'], [['bank' => ['invalid']], 'bank'],
            [['source' => 'manual', 'bank' => 'account:'.$account->id], 'bank'],
            [['source' => 'imported', 'bank' => 'manual:'.$bank->id], 'bank'],
            [['source' => 'imported', 'bank' => 'none'], 'bank'],
        ] as [$filters, $error]) {
            $this->getJson(route('analyses.index', array_replace(['year' => 2026], $filters)))
                ->assertUnprocessable()->assertJsonValidationErrors($error);
        }
    }

    public function test_empty_and_fully_refunded_periods_and_net_credits_render_without_invalid_percentages(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('analyses.index', ['year' => 2026]))
            ->assertOk()->assertSee('Nenhuma despesa encontrada')
            ->assertViewHas('analysis', fn ($analysis) => $analysis['payments']['rows'] === [] && $analysis['payments']['total'] === '0.00');

        $account = $this->account($user);
        $this->imported($account, '10.00');
        $refund = $this->imported($account, '10.00', ['classification' => 'refund']);
        $this->get(route('analyses.index', ['year' => 2026]))->assertOk()->assertSee('0,0%')
            ->assertDontSee('Nenhuma despesa encontrada')
            ->assertViewHas('analysis', fn ($analysis) => $analysis['payments']['total'] === '0.00' && $analysis['payments']['rows'][0]['width'] === 0.0);

        $refund->update(['amount' => '15.00']);
        $this->get(route('analyses.index', ['year' => 2026]))->assertOk()->assertSee('Crédito líquido')
            ->assertSee('R$ -5,00')
            ->assertViewHas('analysis', fn ($analysis) => $analysis['payments']['has_credits'] && $analysis['payments']['rows'][0]['percentage'] === null);
    }

    private function category(User $user, array $extra = []): Category
    {
        return Category::create(array_merge(['user_id' => $user->id, 'name' => 'Alimentação', 'type' => 'expense'], $extra));
    }

    private function manual(User $user, Category $category, string $amount, array $extra = []): Expense
    {
        return Expense::create(array_merge([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => 'expense',
            'date' => '2026-03-10', 'year' => 2026, 'month' => 3, 'amount' => $amount, 'payment_mode' => 'pix',
        ], $extra));
    }

    private function account(User $user, string $type = 'BANK'): PluggyAccount
    {
        $connection = PluggyItem::create(['user_id' => $user->id, 'pluggy_item_id' => (string) Str::uuid(), 'connector_name' => 'Instituição', 'last_event' => 'item/created']);

        return PluggyAccount::create([
            'user_id' => $user->id, 'pluggy_connection_id' => $connection->id,
            'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Conta', 'type' => $type,
        ]);
    }

    private function imported(PluggyAccount $account, string $amount, array $extra = []): PluggyTransaction
    {
        return PluggyTransaction::create(array_merge([
            'user_id' => $account->user_id, 'pluggy_connection_id' => $account->pluggy_connection_id,
            'pluggy_account_id' => $account->id, 'pluggy_transaction_id' => (string) Str::uuid(),
            'description' => 'Compra', 'transaction_date' => '2026-03-10', 'amount' => $amount,
            'classification' => 'expense', 'payment_method' => 'pix', 'status' => 'POSTED', 'affects_totals' => true,
        ], $extra));
    }
}
