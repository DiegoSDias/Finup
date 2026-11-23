<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_finances_requires_authentication(): void
    {
        $this->get(route('finances.index'))->assertRedirect(route('login'));
    }

    public function test_home_links_to_finances_without_rendering_the_annual_summary(): void
    {
        $this->withoutVite();

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee(route('finances.index'), false)
            ->assertDontSee('Total anual de gastos')
            ->assertDontSee('annual-chart-description')
            ->assertDontSee('#visao-financeira');
    }

    public function test_finances_uses_the_selected_year_and_keeps_month_navigation_in_that_year(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $other = User::factory()->create();

        foreach ([[$user, 2025, '125.50'], [$user, 2024, '999.99'], [$other, 2025, '2000.00']] as [$owner, $year, $amount]) {
            $category = Category::create(['user_id' => $owner->id, 'name' => 'Despesa', 'type' => 'expense']);
            Expense::create([
                'user_id' => $owner->id, 'category_id' => $category->id, 'type' => 'expense',
                'date' => "$year-03-10", 'year' => $year, 'month' => 3, 'amount' => $amount,
            ]);
        }

        $this->actingAs($user)->get(route('finances.index', ['year' => 2025]))
            ->assertOk()
            ->assertViewIs('finances.index')
            ->assertViewHas('anoSelecionado', 2025)
            ->assertViewHas('data', fn ($data) => $data['totalExpense'] === 125.50)
            ->assertSee('Total anual de gastos')
            ->assertSee('annual-chart-description')
            ->assertSee(route('finances.index', ['year' => 2024]), false)
            ->assertSee(route('finances.index', ['year' => 2026]), false)
            ->assertSee(route('month.show', [2025, 3]), false)
            ->assertDontSee('#visao-financeira');

        foreach ([3, 4] as $month) {
            $this->get(route('month.show', [2025, $month]))
                ->assertOk()
                ->assertSee(route('finances.index', ['year' => 2025]), false);
        }
    }
}
