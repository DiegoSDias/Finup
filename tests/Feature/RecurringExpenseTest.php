<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\MonthlyBalance;
use App\Models\RecurringExpense;
use App\Models\User;
use App\Services\MonthlyBalanceService;
use App\Services\RecurringExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_recurrence_returns_to_the_original_day_and_never_duplicates_occurrences(): void
    {
        $user = User::factory()->create();
        $category = $this->expenseCategory($user);
        $this->actingAs($user)->post(route('recurring-expenses.store'), $this->payload($category, [
            'date' => '2027-01-31',
            'frequency' => 'monthly',
        ]))->assertSessionHasNoErrors();

        $series = RecurringExpense::where('user_id', $user->id)->sole();
        app(RecurringExpenseService::class)->ensureMonth($user->id, 2027, 2);
        app(RecurringExpenseService::class)->ensureMonth($user->id, 2027, 2);

        $this->assertSame(
            ['2027-01-31', '2027-02-28', '2027-03-31'],
            Expense::where('recurring_expense_id', $series->id)->orderBy('recurrence_occurrence_date')->limit(3)
                ->get()->map(fn (Expense $expense) => $expense->date->format('Y-m-d'))->all()
        );
        $this->assertSame(1, Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-02-28')->count());
    }

    public function test_quarterly_and_annual_recurrences_cross_year_boundaries(): void
    {
        $user = User::factory()->create();
        $category = $this->expenseCategory($user);
        $service = app(RecurringExpenseService::class);

        $quarterly = $service->create($user->id, $this->seriesData($category, ['start_date' => '2027-11-30', 'frequency' => 'quarterly']));
        $annual = $service->create($user->id, $this->seriesData($category, ['start_date' => '2027-12-31', 'frequency' => 'annual']));
        $service->ensureYear($user->id, 2028);

        $this->assertTrue(Expense::where('recurring_expense_id', $quarterly->id)->whereDate('recurrence_occurrence_date', '2028-02-29')->exists());
        $this->assertTrue(Expense::where('recurring_expense_id', $annual->id)->whereDate('recurrence_occurrence_date', '2028-12-31')->exists());
    }

    public function test_single_edit_and_single_deletion_are_preserved_when_the_series_is_updated(): void
    {
        $user = User::factory()->create();
        $category = $this->expenseCategory($user);
        $service = app(RecurringExpenseService::class);
        $series = $service->create($user->id, $this->seriesData($category, ['start_date' => '2027-01-31']));

        $february = Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-02-28')->sole();
        $march = Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-03-31')->sole();
        $service->updateOccurrence($february, $this->occurrenceData($category, ['description' => 'Internet ajustada', 'amount' => '121.50', 'date' => '2027-02-27']), 'single');
        $service->skipOccurrence($march);

        $january = Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-01-31')->sole();
        $service->updateOccurrence($january, $this->occurrenceData($category, ['description' => 'Internet nova', 'amount' => '130.00', 'date' => '2027-01-31']), 'future');

        $this->assertDatabaseHas('expenses', ['id' => $february->id, 'description' => 'Internet ajustada', 'amount' => 121.5, 'recurrence_is_override' => true]);
        $this->assertDatabaseHas('expenses', ['id' => $march->id, 'recurrence_status' => 'skipped', 'recurrence_is_override' => true]);
        $this->assertTrue(Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-04-30')->where('description', 'Internet nova')->where('amount', 130)->exists());
        $this->assertSame(1, Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-03-31')->count());
    }

    public function test_ending_a_series_removes_future_occurrences_and_skipped_occurrences_do_not_affect_totals(): void
    {
        $user = User::factory()->create();
        $category = $this->expenseCategory($user);
        $this->actingAs($user);
        $service = app(RecurringExpenseService::class);
        $series = $service->create($user->id, $this->seriesData($category, ['start_date' => '2027-01-31', 'amount' => '90.00']));
        $february = Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-02-28')->sole();
        $service->skipOccurrence($february);

        $totals = app(MonthlyBalanceService::class)->calculate(2027, 2, $user->id);
        $this->assertSame(0.0, $totals['expenses']);
        $this->assertSame(0.0, $totals['expensesPix']);

        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2027, 'month' => 3, 'closing_balance' => 0]);
        $service->end($series, now()->setDate(2027, 3, 31)->startOfDay(), true);

        $this->assertFalse($series->fresh()->is_active);
        $this->assertFalse(Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-03-31')->exists());
        $this->assertFalse(Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-04-30')->exists());
    }

    public function test_recurrence_routes_are_private_and_the_list_is_available_to_the_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = $this->expenseCategory($user);
        $this->actingAs($user)->post(route('recurring-expenses.store'), $this->payload($category))->assertSessionHasNoErrors();
        $series = RecurringExpense::where('user_id', $user->id)->sole();

        $this->withoutVite()->get(route('recurring-expenses.index'))->assertOk()->assertSee('Despesas recorrentes');
        $this->actingAs($other)->post(route('recurring-expenses.end', $series), ['from_date' => '2027-02-01'])->assertNotFound();
        $this->actingAs($other)->post(route('recurring-expenses.store'), $this->payload($category))->assertSessionHasErrors('category_id');
    }

    public function test_occurrence_edit_and_delete_routes_use_recurrence_scopes_without_exposing_installments(): void
    {
        $user = User::factory()->create();
        $category = $this->expenseCategory($user);
        $this->actingAs($user)->post(route('recurring-expenses.store'), $this->payload($category))->assertSessionHasNoErrors();
        $series = RecurringExpense::where('user_id', $user->id)->sole();
        $occurrence = Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-01-31')->sole();

        $this->withoutVite()->get(route('expenses.edit', $occurrence))
            ->assertOk()
            ->assertSee('Somente esta ocorrência')
            ->assertSee('Esta e as próximas')
            ->assertDontSee('Compra parcelada?');

        $this->put(route('expenses.update', $occurrence), $this->occurrenceData($category, [
            'description' => 'Internet individual',
            'amount' => '125.00',
            'date' => '2027-01-30',
            'recurrence_scope' => 'single',
            'bank_choice' => 'none',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', ['id' => $occurrence->id, 'description' => 'Internet individual', 'recurrence_is_override' => true]);
        $this->delete(route('expenses.destroy', $occurrence), ['recurrence_scope' => 'single'])->assertRedirect();
        $this->assertDatabaseHas('expenses', ['id' => $occurrence->id, 'recurrence_status' => 'skipped']);
    }

    public function test_future_scope_from_the_edit_form_updates_future_occurrences(): void
    {
        $user = User::factory()->create();
        $category = $this->expenseCategory($user);
        $this->actingAs($user)->post(route('recurring-expenses.store'), $this->payload($category))->assertSessionHasNoErrors();
        $series = RecurringExpense::where('user_id', $user->id)->sole();
        $january = Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-01-31')->sole();

        $this->put(route('expenses.update', $january), $this->occurrenceData($category, [
            'description' => 'Internet reajustada',
            'amount' => '140.00',
            'date' => '2027-01-31',
            'recurrence_scope' => 'future',
            'bank_choice' => 'none',
        ]))->assertSessionHasNoErrors();

        $this->assertTrue(Expense::where('recurring_expense_id', $series->id)
            ->whereDate('recurrence_occurrence_date', '2027-02-28')
            ->where('description', 'Internet reajustada')
            ->where('amount', 140)
            ->exists());
    }

    public function test_series_edit_rebases_only_future_occurrences_and_completed_series_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = $this->expenseCategory($user);
        $this->actingAs($user);

        $this->post(route('recurring-expenses.store'), $this->payload($category))->assertSessionHasNoErrors();
        $series = RecurringExpense::where('user_id', $user->id)->sole();
        $this->withoutVite()->get(route('recurring-expenses.edit', $series))->assertOk()->assertSee('Nova data-base');

        $this->put(route('recurring-expenses.update', $series), $this->payload($category, [
            'description' => 'Internet nova data',
            'amount' => '135.00',
            'date' => '2027-02-15',
        ]))->assertSessionHasNoErrors();

        $this->assertFalse(Expense::where('recurring_expense_id', $series->id)->whereDate('recurrence_occurrence_date', '2027-01-31')->exists());
        $this->assertTrue(Expense::where('recurring_expense_id', $series->id)
            ->whereDate('recurrence_occurrence_date', '2027-02-15')
            ->where('description', 'Internet nova data')
            ->where('amount', 135)
            ->exists());

        \Carbon\Carbon::setTestNow('2027-06-01');
        try {
            $completed = app(RecurringExpenseService::class)->create($user->id, $this->seriesData($category, [
                'start_date' => '2027-01-31',
                'end_date' => '2027-05-31',
            ]));
            $this->assertTrue($completed->canBeDeleted());

            $this->delete(route('recurring-expenses.destroy', $completed))->assertSessionHasNoErrors();
            $this->assertDatabaseMissing('recurring_expenses', ['id' => $completed->id]);
            $this->assertDatabaseHas('expenses', ['user_id' => $user->id, 'recurring_expense_id' => null]);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_recurrence_migration_refuses_a_data_losing_rollback(): void
    {
        $user = User::factory()->create();
        $category = $this->expenseCategory($user);
        app(RecurringExpenseService::class)->create($user->id, $this->seriesData($category));
        $migration = require database_path('migrations/2026_10_28_000000_create_recurring_expenses_table.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reversão bloqueada');
        $migration->down();
    }

    private function expenseCategory(User $user): Category
    {
        return Category::create(['user_id' => $user->id, 'name' => 'Moradia', 'type' => 'expense']);
    }

    private function payload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'description' => 'Internet',
            'amount' => '120.00',
            'category_id' => $category->id,
            'date' => '2027-01-31',
            'frequency' => 'monthly',
            'payment_mode' => 'pix',
            'bank_choice' => 'none',
        ], $overrides);
    }

    private function seriesData(Category $category, array $overrides = []): array
    {
        $data = $this->payload($category, $overrides);

        return [
            'category_id' => $data['category_id'],
            'manual_bank_id' => null,
            'bank_status' => 'none',
            'payment_mode' => $data['payment_mode'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'frequency' => $data['frequency'],
            'start_date' => $data['start_date'] ?? $data['date'],
            'end_date' => $data['end_date'] ?? null,
        ];
    }

    private function occurrenceData(Category $category, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $category->id,
            'type' => 'expense',
            'manual_bank_id' => null,
            'bank_status' => 'none',
            'payment_mode' => 'pix',
            'description' => 'Internet',
            'amount' => '120.00',
            'date' => '2027-01-31',
        ], $overrides);
    }
}
