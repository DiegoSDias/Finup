<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Group;
use App\Models\MonthlyBalance;
use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\PluggyTransactionRule;
use App\Models\User;
use App\Services\Pluggy\PluggyService;
use App\Services\Pluggy\PluggyTransactionImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Queue::fake();
    }

    private function category(User $user, string $type = 'expense'): Category
    {
        return Category::create(['user_id' => $user->id, 'name' => 'Categoria', 'type' => $type]);
    }

    private function expense(User $user, Category $category, array $extra = []): Expense
    {
        return Expense::create(array_merge([
            'user_id' => $user->id, 'category_id' => $category->id, 'date' => '2026-10-01',
            'month' => 10, 'year' => 2026, 'type' => 'expense', 'payment_mode' => 'pix',
            'description' => 'Despesa privada', 'amount' => 20, 'is_installment' => false,
        ], $extra));
    }

    private function payload(Category $category): array
    {
        return ['category_id' => $category->id, 'date' => '2026-10-01', 'month' => 10,
            'year' => 2026, 'type' => 'expense', 'payment_mode' => 'pix', 'amount' => 20, 'bank_choice' => 'none'];
    }

    public function test_other_users_expenses_are_inaccessible_and_unchanged(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $ownCategory = $this->category($a);
        $other = $this->expense($b, $this->category($b));
        $this->actingAs($a);

        $this->get(route('expenses.show', $other))->assertNotFound();
        $this->get(route('expenses.edit', $other))->assertNotFound();
        $this->put(route('expenses.update', $other), $this->payload($ownCategory))->assertNotFound();
        $this->delete(route('expenses.destroy', $other))->assertNotFound();
        $this->assertDatabaseHas('expenses', ['id' => $other->id, 'user_id' => $b->id, 'description' => 'Despesa privada']);
        $this->get('/expenses/999999/edit')->assertNotFound();
    }

    public function test_installment_operations_are_scoped_even_when_group_ids_collide(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $group = (string) Str::uuid();
        $installment = ['is_installment' => true, 'installments_group' => $group, 'current_installment' => 1, 'total_installments' => 2];
        $own = $this->expense($a, $this->category($a), $installment);
        $other = $this->expense($b, $this->category($b), $installment);
        $this->actingAs($a)->delete(route('expenses.destroy', $own))->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['id' => $own->id]);
        $this->assertDatabaseHas('expenses', ['id' => $other->id, 'user_id' => $b->id]);
    }

    public function test_expense_category_must_belong_to_user_and_match_type(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $own = $this->category($a);
        $foreign = $this->category($b);
        $wrongType = $this->category($a, 'income');
        $expense = $this->expense($a, $own);
        $this->actingAs($a);

        foreach ([$foreign->id, $wrongType->id, 999999] as $id) {
            $payload = array_merge($this->payload($own), ['category_id' => $id]);
            $this->post(route('expenses.store'), $payload)->assertSessionHasErrors('category_id');
            $this->put(route('expenses.update', $expense), $payload)->assertSessionHasErrors('category_id');
        }
        $this->post(route('expenses.store'), array_merge($this->payload($own), ['category_id' => 'new']))->assertSessionHasErrors('new_category');
        $this->post(route('expenses.store'), $this->payload($own))->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('expenses.show', $expense))->assertOk();
        $this->get(route('expenses.edit', $expense))->assertOk();
        $this->put(route('expenses.update', $expense), $this->payload($own))->assertSessionHasNoErrors()->assertRedirect();
        $this->delete(route('expenses.destroy', $expense))->assertRedirect();
    }

    public function test_categories_and_groups_are_protected_and_validated(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $own = $this->category($a);
        $foreign = $this->category($b);
        $ownGroup = Group::create(['user_id' => $a->id, 'name' => 'Meu grupo']);
        $foreignGroup = Group::create(['user_id' => $b->id, 'name' => 'Grupo privado']);
        $this->actingAs($a);

        foreach (['categories' => $foreign->id, 'category-groups' => $foreignGroup->id] as $resource => $id) {
            $this->get("/$resource/$id/edit")->assertNotFound();
            $this->put("/$resource/$id", ['name' => 'Alterado', 'type' => 'expense'])->assertNotFound();
            $this->delete("/$resource/$id")->assertNotFound();
        }
        $this->post(route('categories.store'), ['name' => 'Teste', 'type' => 'expense', 'group_id' => $foreignGroup->id])->assertSessionHasErrors('group_id');
        $this->put(route('categories.update', $own), ['name' => 'Teste', 'type' => 'expense', 'group_id' => $foreignGroup->id])->assertSessionHasErrors('group_id');
        $this->post(route('category-groups.store'), ['name' => 'Teste', 'expense_categories' => [$foreign->id]])->assertSessionHasErrors('expense_categories.0');
        $this->put(route('category-groups.update', $ownGroup), ['name' => 'Teste', 'expense_categories' => [$foreign->id]])->assertSessionHasErrors('expense_categories.0');
        $this->post(route('category-groups.store'), ['name' => 'Teste', 'income_categories' => [$own->id]])->assertSessionHasErrors('income_categories.0');
        $this->post(route('categories.store'), ['name' => '', 'type' => 'invalid'])->assertSessionHasErrors(['name', 'type']);
        $this->post(route('category-groups.store'), ['name' => '', 'expense_categories' => 'invalid'])->assertSessionHasErrors(['name', 'expense_categories']);
        $this->get(route('categories.edit', $own))->assertOk();
        $this->get(route('category-groups.edit', $ownGroup))->assertOk();
        $this->put(route('categories.update', $own), ['name' => 'Minha categoria', 'type' => 'expense', 'group_id' => $ownGroup->id])->assertSessionHasNoErrors()->assertRedirect();
        $this->put(route('category-groups.update', $ownGroup), ['name' => 'Meu grupo', 'expense_categories' => [$own->id]])->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('categories.store'), ['name' => 'Nova', 'type' => 'income'])->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('category-groups.store'), ['name' => 'Novo grupo'])->assertSessionHasNoErrors()->assertRedirect();
        $this->delete(route('categories.destroy', $own))->assertRedirect();
        $this->delete(route('category-groups.destroy', $ownGroup))->assertRedirect();
        $this->assertDatabaseHas('categories', ['id' => $foreign->id, 'user_id' => $b->id, 'group_id' => null]);
        $this->assertDatabaseHas('groups', ['id' => $foreignGroup->id, 'user_id' => $b->id]);
    }

    public function test_pluggy_resources_and_monthly_balances_are_isolated(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $connection = PluggyItem::create(['user_id' => $b->id, 'pluggy_item_id' => (string) Str::uuid(), 'last_event' => 'item/created', 'is_active' => true]);
        $account = PluggyAccount::create(['user_id' => $b->id, 'pluggy_connection_id' => $connection->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Conta privada', 'type' => 'BANK']);
        $transaction = PluggyTransaction::create(['user_id' => $b->id, 'pluggy_connection_id' => $connection->id, 'pluggy_account_id' => $account->id, 'pluggy_transaction_id' => (string) Str::uuid(), 'description' => 'Transação privada', 'transaction_date' => '2026-10-01', 'amount' => 20, 'classification' => 'expense']);
        $rule = PluggyTransactionRule::create(['user_id' => $b->id, 'phrase' => 'privado', 'action' => PluggyTransactionRule::ACTION_EXCLUDE]);
        MonthlyBalance::create(['user_id' => $b->id, 'year' => 2026, 'month' => 9, 'closing_balance' => 999]);
        $this->actingAs($a);

        $this->get(route('pluggy.transactions.show', $transaction))->assertNotFound();
        $this->get(route('pluggy.transactions.edit', $transaction))->assertNotFound();
        $this->put(route('pluggy.transactions.update', $transaction), [])->assertNotFound();
        $this->delete(route('pluggy.transactions.destroy', $transaction))->assertNotFound();
        $this->post(route('pluggy.connections.sync', $connection))->assertNotFound();
        $this->delete(route('pluggy.connections.destroy', $connection))->assertNotFound();
        $this->post(route('pluggy.connections.reactivate', $connection))->assertNotFound();
        $this->patch(route('pluggy.transaction-rules.update', $rule), [])->assertNotFound();
        $this->delete(route('pluggy.transaction-rules.destroy', $rule))->assertNotFound();
        $this->get(route('pluggy.accounts.index'))->assertOk()->assertJsonCount(0, 'data');
        $this->get(route('month.show', [2026, 10]))->assertOk()->assertViewHas('balanceMonthPrevious', 0);
        $this->post(route('month.close', [2026, 10]))->assertRedirect();
        $this->assertDatabaseHas('monthly_balances', ['user_id' => $b->id, 'year' => 2026, 'month' => 9, 'closing_balance' => 999]);
    }

    public function test_inconsistent_legacy_links_do_not_expose_or_delete_another_users_data(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $category = $this->category($a);
        $group = Group::create(['user_id' => $a->id, 'name' => 'Grupo']);
        $foreignCategory = $this->category($b);
        $foreignCategory->update(['group_id' => $group->id]);
        $foreignExpense = $this->expense($b, $category);
        $this->actingAs($a)->delete(route('categories.destroy', $category))->assertStatus(409);
        $this->delete(route('category-groups.destroy', $group))->assertStatus(409);
        $this->assertDatabaseHas('expenses', ['id' => $foreignExpense->id, 'user_id' => $b->id]);
        $this->assertDatabaseHas('categories', ['id' => $foreignCategory->id, 'group_id' => $group->id]);
    }

    public function test_new_pluggy_connection_requires_proven_ownership(): void
    {
        $user = User::factory()->create();
        $itemId = (string) Str::uuid();
        $service = $this->mock(PluggyService::class);
        $service->shouldReceive('getItem')->once()->with($itemId)->andReturn([]);
        $this->actingAs($user)->postJson(route('pluggy.items.store'), ['itemId' => $itemId])->assertForbidden();
        $this->assertDatabaseMissing('pluggy_items', ['pluggy_item_id' => $itemId]);
    }

    public function test_owner_can_manage_imported_transaction_but_cannot_use_someone_elses_category(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = $this->category($user);
        $foreign = $this->category($other);
        $connection = PluggyItem::create(['user_id' => $user->id, 'pluggy_item_id' => (string) Str::uuid(), 'last_event' => 'item/created']);
        $account = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $connection->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Minha conta', 'type' => 'BANK']);
        $transaction = PluggyTransaction::create(['user_id' => $user->id, 'pluggy_connection_id' => $connection->id, 'pluggy_account_id' => $account->id, 'pluggy_transaction_id' => (string) Str::uuid(), 'description' => 'Minha transação', 'transaction_date' => '2026-10-01', 'amount' => 20, 'classification' => 'expense']);
        $payload = ['description' => 'Alterada', 'transaction_date' => '2026-10-02', 'category_id' => $foreign->id];
        $this->actingAs($user)->get(route('pluggy.transactions.show', $transaction))->assertOk();
        $this->get(route('pluggy.transactions.edit', $transaction))->assertOk();
        $this->put(route('pluggy.transactions.update', $transaction), $payload)->assertSessionHasErrors('category_id');
        $payload['category_id'] = $category->id;
        $this->put(route('pluggy.transactions.update', $transaction), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->delete(route('pluggy.transactions.destroy', $transaction))->assertRedirect();
        $this->assertNotNull($transaction->refresh()->deleted_by_user_at);
    }

    public function test_new_pluggy_connection_can_be_created_when_owner_is_confirmed(): void
    {
        $user = User::factory()->create();
        $itemId = (string) Str::uuid();
        $this->mock(PluggyService::class)->shouldReceive('getItem')->once()->with($itemId)
            ->andReturn(['clientUserId' => (string) $user->id]);
        $this->actingAs($user)->postJson(route('pluggy.items.store'), ['itemId' => $itemId])->assertCreated();
        $this->assertDatabaseHas('pluggy_items', ['pluggy_item_id' => $itemId, 'user_id' => $user->id]);
    }

    public function test_webhook_requires_secret_and_cannot_transfer_a_connection(): void
    {
        config()->set('queue.default', 'database');
        $a = User::factory()->create();
        $b = User::factory()->create();
        $item = PluggyItem::create(['user_id' => $b->id, 'pluggy_item_id' => (string) Str::uuid(), 'last_event' => 'item/created']);
        $payload = ['event' => 'item/deleted', 'eventId' => (string) Str::uuid(), 'itemId' => $item->pluggy_item_id, 'clientUserId' => (string) $a->id];
        config()->set('services.pluggy.webhook_secret', null);
        $this->postJson(route('pluggy.webhook'), $payload)->assertUnauthorized();
        config()->set('services.pluggy.webhook_secret', 'test-secret');
        $this->postJson(route('pluggy.webhook', ['token' => 'test-secret']), $payload)->assertUnauthorized();
        $this->assertDatabaseHas('pluggy_items', ['id' => $item->id, 'user_id' => $b->id, 'deleted_at' => null]);
        $this->assertDatabaseMissing('pluggy_webhook_events', ['event_id' => $payload['eventId']]);
        Queue::assertNothingPushed();
    }

    public function test_import_does_not_transfer_a_transaction_between_users(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $connectionA = PluggyItem::create(['user_id' => $a->id, 'pluggy_item_id' => (string) Str::uuid(), 'last_event' => 'item/created']);
        $connectionB = PluggyItem::create(['user_id' => $b->id, 'pluggy_item_id' => (string) Str::uuid(), 'last_event' => 'item/created']);
        $accountA = PluggyAccount::create(['user_id' => $a->id, 'pluggy_connection_id' => $connectionA->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'A', 'type' => 'BANK']);
        $accountB = PluggyAccount::create(['user_id' => $b->id, 'pluggy_connection_id' => $connectionB->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'B', 'type' => 'BANK']);
        $externalId = (string) Str::uuid();
        $existing = PluggyTransaction::create(['user_id' => $b->id, 'pluggy_connection_id' => $connectionB->id, 'pluggy_account_id' => $accountB->id, 'pluggy_transaction_id' => $externalId, 'description' => 'Privada', 'transaction_date' => '2026-10-01', 'amount' => 20, 'classification' => 'expense']);
        try {
            app(PluggyTransactionImporter::class)->import($connectionA, $accountA, [['id' => $externalId]]);
            $this->fail('A colisão deveria ser recusada.');
        } catch (\LogicException $exception) {
            $this->assertDatabaseHas('pluggy_transactions', ['id' => $existing->id, 'user_id' => $b->id, 'description' => 'Privada']);
        }
    }
}
