<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\MonthlyBalance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_installments_preserve_cents_and_original_day_through_short_months_and_year_change(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Teste', 'type' => 'expense']);
        $this->actingAs($user)->post(route('expenses.store'), [
            'category_id' => $category->id, 'date' => '2027-01-31', 'type' => 'expense',
            'amount' => '100.00', 'payment_mode' => 'credito', 'bank_choice' => 'none', 'is_installment' => '1', 'total_installments' => 3,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $items = Expense::where('user_id', $user->id)->orderBy('current_installment')->get();
        $this->assertSame(['33.34', '33.33', '33.33'], $items->pluck('amount')->all());
        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], $items->map(fn ($item) => $item->date->format('Y-m-d'))->all());
        $this->assertSame([1, 2, 3], $items->pluck('month')->all());
        $this->withoutVite();
        $this->get(route('expenses.edit', $items[1]))->assertOk()->assertSee('Somente esta parcela')->assertSee('Todo o grupo de parcelas')
            ->assertSee('id="installmentControls" class="hidden"', false);
        $this->get(route('month.show', [2027, 2]))->assertOk()->assertDontSee('Escopo da exclusão')
            ->assertSee(route('expenses.confirm-delete', $items[1]), false);
        $this->get(route('expenses.confirm-delete', $items[1]))->assertOk()->assertSee('Somente esta parcela')->assertSee('Todo o grupo de parcelas');
    }

    public function test_single_edit_and_delete_leave_other_installments_and_recalculate_closed_months(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Teste', 'type' => 'expense']);
        $this->actingAs($user);
        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2026, 'month' => 12, 'closing_balance' => 100]);
        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2027, 'month' => 1, 'closing_balance' => 100]);
        MonthlyBalance::create(['user_id' => $user->id, 'year' => 2027, 'month' => 2, 'closing_balance' => 100]);
        $payload = ['category_id' => $category->id, 'date' => '2026-12-31', 'type' => 'expense',
            'amount' => '3.00', 'payment_mode' => 'pix', 'bank_choice' => 'none', 'is_installment' => '1', 'total_installments' => 3];
        $this->post(route('expenses.store'), $payload)->assertSessionHasNoErrors();
        $items = Expense::where('user_id', $user->id)->orderBy('current_installment')->get();
        $this->assertSame(['2026-12-31', '2027-01-31', '2027-02-28'], $items->map(fn ($item) => $item->date->format('Y-m-d'))->all());
        $singlePayload = array_merge($payload, ['installment_scope' => 'single', 'date' => '2027-01-30', 'amount' => '2.00']);
        unset($singlePayload['is_installment'], $singlePayload['total_installments']);
        $this->put(route('expenses.update', $items[1]), $singlePayload)->assertSessionHasNoErrors();
        $this->assertSame('1.00', $items[0]->fresh()->amount);
        $this->assertSame('2.00', $items[1]->fresh()->amount);
        $this->assertSame('1.00', $items[2]->fresh()->amount);
        $this->delete(route('expenses.destroy', $items[1]), ['installment_scope' => 'single'])->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['id' => $items[1]->id]);
        $this->assertDatabaseHas('expenses', ['id' => $items[0]->id]);
        $this->assertDatabaseHas('expenses', ['id' => $items[2]->id]);
        $this->assertEquals(-1, MonthlyBalance::where('user_id', $user->id)->where('year', 2026)->where('month', 12)->value('closing_balance'));
        $this->assertEquals(-1, MonthlyBalance::where('user_id', $user->id)->where('year', 2027)->where('month', 1)->value('closing_balance'));
        $this->assertEquals(-2, MonthlyBalance::where('user_id', $user->id)->where('year', 2027)->where('month', 2)->value('closing_balance'));
    }

    public function test_group_edit_recreates_with_new_total_and_count_and_group_delete_removes_all(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Teste', 'type' => 'expense']);
        $this->actingAs($user);
        $payload = ['category_id' => $category->id, 'date' => '2028-01-31', 'type' => 'expense',
            'amount' => '1.00', 'payment_mode' => 'credito', 'bank_choice' => 'none', 'is_installment' => '1', 'total_installments' => 2];
        $this->post(route('expenses.store'), $payload)->assertSessionHasNoErrors();
        $old = Expense::where('user_id', $user->id)->orderBy('current_installment')->get();
        $this->assertSame(['0.50', '0.50'], $old->pluck('amount')->all());
        $this->put(route('expenses.update', $old[1]), array_merge($payload, [
            'amount' => '1.01', 'total_installments' => 3, 'installment_scope' => 'group',
        ]))->assertSessionHasNoErrors();
        $new = Expense::where('user_id', $user->id)->orderBy('current_installment')->get();
        $this->assertSame(['0.34', '0.34', '0.33'], $new->pluck('amount')->all());
        $this->assertSame(['2028-01-31', '2028-02-29', '2028-03-31'], $new->map(fn ($item) => $item->date->format('Y-m-d'))->all());
        $this->delete(route('expenses.destroy', $new[1]), ['installment_scope' => 'group'])->assertRedirect();
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_too_small_a_total_and_more_than_two_decimal_places_are_rejected(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Teste', 'type' => 'expense']);
        $this->actingAs($user);
        $payload = ['category_id' => $category->id, 'date' => '2026-04-30', 'type' => 'expense',
            'amount' => '0.01', 'payment_mode' => 'credito', 'bank_choice' => 'none', 'is_installment' => '1', 'total_installments' => 2];
        $this->post(route('expenses.store'), $payload)->assertSessionHasErrors('amount');
        $this->post(route('expenses.store'), array_merge($payload, ['amount' => '1.001']))->assertSessionHasErrors('amount');
        $this->post(route('expenses.store'), array_merge($payload, ['amount' => '1.00', 'total_installments' => null]))->assertSessionHasErrors('total_installments');
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_delete_without_scope_only_removes_selected_installment_and_confirmation_is_private(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Teste', 'type' => 'expense']);
        $this->actingAs($user)->post(route('expenses.store'), [
            'category_id' => $category->id, 'date' => '2027-01-31', 'type' => 'expense',
            'amount' => '10.00', 'payment_mode' => 'credito', 'bank_choice' => 'none', 'is_installment' => '1', 'total_installments' => 2,
        ])->assertSessionHasNoErrors();
        $items = Expense::where('user_id', $user->id)->orderBy('current_installment')->get();
        $this->actingAs($other)->get(route('expenses.confirm-delete', $items[0]))->assertNotFound();
        $this->actingAs($user)->delete(route('expenses.destroy', $items[0]))->assertRedirect(route('month.show', [2027, 1]));
        $this->assertDatabaseMissing('expenses', ['id' => $items[0]->id]);
        $this->assertDatabaseHas('expenses', ['id' => $items[1]->id]);
    }
}
