<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PluggyAccount;
use App\Models\PluggyCategoryRule;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use App\Services\Pluggy\PluggyTransactionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PluggyCategoryRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_category_can_be_created_with_a_rule_and_invalid_input_creates_nothing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withoutVite()->get(route('pluggy.connections'))
            ->assertOk()->assertSee('+ Criar nova categoria');
        $this->post(route('pluggy.category-rules.store'), [
            'phrase' => 'Uber', 'category_id' => 'new', 'new_category' => 'Transporte',
        ])->assertSessionHasErrors('new_category_type');
        $this->assertDatabaseMissing('categories', ['user_id' => $user->id, 'name' => 'Transporte']);
        $this->post(route('pluggy.category-rules.store'), [
            'phrase' => 'Uber', 'category_id' => 'new', 'new_category' => ' Transporte ', 'new_category_type' => 'expense',
        ])->assertSessionHasNoErrors();
        $category = Category::where('user_id', $user->id)->where('name', 'Transporte')->sole();
        $this->assertSame('expense', $category->type);
        $this->assertDatabaseHas('pluggy_category_rules', ['user_id' => $user->id, 'phrase' => 'Uber', 'category_id' => $category->id]);
    }

    public function test_rules_apply_by_specificity_and_period_preserve_manual_choices_and_restore_pluggy_fallback(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $item = PluggyItem::factory()->create(['user_id' => $user->id]);
        $account = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Conta', 'type' => 'BANK']);
        $uber = Category::create(['user_id' => $user->id, 'name' => 'Uber', 'type' => 'expense']);
        $food = Category::create(['user_id' => $user->id, 'name' => 'Comida', 'type' => 'expense']);
        $payload = ['id' => (string) Str::uuid(), 'description' => 'UBER EATS', 'date' => '2027-02-10', 'amount' => -50, 'type' => 'DEBIT', 'status' => 'POSTED', 'currencyCode' => 'BRL', 'category' => 'Food and Drink'];
        app(PluggyTransactionImporter::class)->import($item, $account, [$payload]);
        $transaction = PluggyTransaction::where('pluggy_transaction_id', $payload['id'])->sole();
        $this->assertSame('Food and Drink', $transaction->category_label);
        $this->actingAs($user)->post(route('pluggy.category-rules.store'), ['phrase' => 'Uber', 'category_id' => $uber->id])->assertSessionHasNoErrors();
        $this->post(route('pluggy.category-rules.store'), ['phrase' => 'Uber Eats', 'category_id' => $food->id, 'starts_on' => '2027-01-01'])->assertSessionHasNoErrors();
        $rule = PluggyCategoryRule::where('phrase', 'Uber Eats')->sole();
        $this->assertSame($food->id, $transaction->fresh()->category_id);
        $this->actingAs($other)->delete(route('pluggy.category-rules.destroy', $rule), ['mode' => 'delete'])->assertNotFound();
        $this->actingAs($user)->delete(route('pluggy.category-rules.destroy', $rule), ['mode' => 'end', 'ends_before' => '2027-02-01'])->assertSessionHasNoErrors();
        $this->assertSame($uber->id, $transaction->fresh()->category_id);
        $general = PluggyCategoryRule::where('phrase', 'Uber')->sole();
        $this->delete(route('pluggy.category-rules.destroy', $general), ['mode' => 'delete'])->assertSessionHasNoErrors();
        $this->assertSame('Food and Drink', $transaction->fresh()->category_label);
        $this->put(route('pluggy.transactions.update', $transaction), ['description' => 'UBER EATS', 'transaction_date' => '2027-02-10', 'category_id' => $food->id])->assertSessionHasNoErrors();
        app(PluggyTransactionImporter::class)->import($item, $account, [$payload]);
        $this->assertSame($food->id, $transaction->fresh()->category_id);
        $this->assertTrue($transaction->fresh()->user_modified_category);
    }
}
