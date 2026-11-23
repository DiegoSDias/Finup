<?php

namespace Tests\Feature;

use App\Models\PluggyItem;
use App\Models\User;
use App\Jobs\ProcessPluggyWebhook;
use App\Models\PluggyWebhookEvent;
use App\Services\Pluggy\PluggyService;
use App\Services\Pluggy\PluggySyncService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluggyWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.pluggy.webhook_secret', 'webhook-secret');
        config()->set('queue.default', 'database');
    }

    public function test_it_records_an_item_created_by_pluggy_for_its_user(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->postJson(route('pluggy.webhook', ['token' => 'webhook-secret']), [
            'event' => 'item/created',
            'eventId' => 'a34715d1-a440-4360-bc75-916fe6d9ee00',
            'itemId' => 'b34715d1-a440-4360-bc75-916fe6d9ee00',
            'clientUserId' => (string) $user->id,
        ]);

        $response->assertNoContent(200);

        $this->assertDatabaseMissing('pluggy_items', ['pluggy_item_id' => 'b34715d1-a440-4360-bc75-916fe6d9ee00']);
        $this->assertDatabaseHas('pluggy_webhook_events', ['event_id' => 'a34715d1-a440-4360-bc75-916fe6d9ee00']);
        Queue::assertPushed(ProcessPluggyWebhook::class);
    }

    public function test_it_requires_the_configured_webhook_secret(): void
    {
        config()->set('services.pluggy.webhook_secret', 'webhook-secret');

        $this->postJson(route('pluggy.webhook'), [
            'event' => 'item/created',
        ])->assertUnauthorized();
    }

    public function test_it_marks_a_deleted_item_as_deleted(): void
    {
        Queue::fake();
        $item = PluggyItem::factory()->create();

        $this->postJson(route('pluggy.webhook', ['token' => 'webhook-secret']), [
            'event' => 'item/deleted',
            'eventId' => 'c34715d1-a440-4360-bc75-916fe6d9ee00',
            'itemId' => $item->pluggy_item_id,
            'clientUserId' => (string) $item->user_id,
        ])->assertNoContent(200);

        $this->assertDatabaseHas('pluggy_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_it_ignores_a_duplicate_webhook_event(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $payload = [
            'event' => 'item/created',
            'eventId' => 'd34715d1-a440-4360-bc75-916fe6d9ee00',
            'itemId' => 'e34715d1-a440-4360-bc75-916fe6d9ee00',
            'clientUserId' => (string) $user->id,
        ];

        $this->postJson(route('pluggy.webhook', ['token' => 'webhook-secret']), $payload)->assertNoContent(200);
        $this->postJson(route('pluggy.webhook', ['token' => 'webhook-secret']), $payload)->assertNoContent(200);

        $this->assertDatabaseCount('pluggy_webhook_events', 1);
        Queue::assertPushed(ProcessPluggyWebhook::class, 1);
    }

    public function test_it_rejects_invalid_requests_without_saving_or_dispatching_events(): void
    {
        Queue::fake();

        $this->post(route('pluggy.webhook', ['token' => 'webhook-secret']), [])->assertStatus(415);
        $this->postJson(route('pluggy.webhook', ['token' => 'wrong']), [])->assertUnauthorized();
        $this->postJson(route('pluggy.webhook', ['token' => 'webhook-secret']), [
            'event' => 'item/created', 'eventId' => 'invalid',
        ])->assertBadRequest();

        $this->assertDatabaseCount('pluggy_webhook_events', 0);
        Queue::assertNothingPushed();
    }

    public function test_header_authentication_and_duplicate_do_not_mutate_connection(): void
    {
        Queue::fake();
        $item = PluggyItem::factory()->create(['is_active' => true, 'last_sync_status' => 'success']);
        $payload = ['event' => 'item/deleted', 'eventId' => (string) \Illuminate\Support\Str::uuid(), 'itemId' => $item->pluggy_item_id, 'clientUserId' => (string) $item->user_id];
        $this->withHeader('X-Pluggy-Webhook-Secret', 'webhook-secret')->postJson(route('pluggy.webhook'), $payload)->assertOk();
        $this->withHeader('X-Pluggy-Webhook-Secret', 'webhook-secret')->postJson(route('pluggy.webhook'), $payload)->assertOk();
        $this->assertTrue((bool) $item->fresh()->is_active);
        $this->assertNull($item->fresh()->deleted_at);
        Queue::assertPushed(ProcessPluggyWebhook::class, 1);
        $payload['event'] = 'item/updated';
        $this->withHeader('X-Pluggy-Webhook-Secret', 'webhook-secret')->postJson(route('pluggy.webhook'), $payload)->assertStatus(409);
    }

    public function test_query_authentication_can_be_disabled(): void
    {
        Queue::fake();
        config()->set('services.pluggy.webhook_allow_query_token', false);
        $payload = ['event' => 'item/updated', 'eventId' => (string) \Illuminate\Support\Str::uuid(), 'itemId' => (string) \Illuminate\Support\Str::uuid()];
        $this->postJson(route('pluggy.webhook', ['token' => 'webhook-secret']), $payload)->assertUnauthorized();
        $this->withHeader('X-Pluggy-Webhook-Secret', 'webhook-secret')->postJson(route('pluggy.webhook'), $payload)->assertOk();
    }

    public function test_failed_processing_keeps_event_pending_and_can_be_retried_without_sensitive_error(): void
    {
        $item = PluggyItem::factory()->create(['is_active' => true]);
        $event = PluggyWebhookEvent::create(['event_id' => (string) \Illuminate\Support\Str::uuid(), 'event' => 'item/updated', 'pluggy_item_id' => $item->pluggy_item_id,
            'payload' => ['itemId' => $item->pluggy_item_id], 'received_at' => now()]);
        $sync = \Mockery::mock(PluggySyncService::class);
        $sync->shouldReceive('sync')->once()->andThrow(new \RuntimeException('sensitive secret'));
        $pluggy = \Mockery::mock(PluggyService::class);
        try {
            (new ProcessPluggyWebhook($event->id))->handle($sync, $pluggy);
            $this->fail('A falha deve permitir nova tentativa.');
        } catch (\RuntimeException $exception) {
            $this->assertStringNotContainsString('sensitive secret', $exception->getMessage());
        }
        $this->assertNull($event->fresh()->processed_at);
        $this->assertStringNotContainsString('sensitive secret', $event->fresh()->processing_error);
        $retry = \Mockery::mock(PluggySyncService::class);
        $retry->shouldReceive('sync')->once()->andReturn(['status' => 'success']);
        (new ProcessPluggyWebhook($event->id))->handle($retry, $pluggy);
        $this->assertNotNull($event->fresh()->processed_at);
        $this->assertNull($event->fresh()->processing_error);
    }

    public function test_unknown_item_creation_requires_matching_provider_owner(): void
    {
        $user = User::factory()->create();
        $itemId = (string) \Illuminate\Support\Str::uuid();
        $event = PluggyWebhookEvent::create(['event_id' => (string) \Illuminate\Support\Str::uuid(), 'event' => 'item/created', 'pluggy_item_id' => $itemId,
            'payload' => ['itemId' => $itemId, 'clientUserId' => (string) $user->id], 'received_at' => now()]);
        $pluggy = \Mockery::mock(PluggyService::class);
        $pluggy->shouldReceive('getItem')->once()->with($itemId)->andReturn(['clientUserId' => '999999']);
        try {
            (new ProcessPluggyWebhook($event->id))->handle(\Mockery::mock(PluggySyncService::class), $pluggy);
            $this->fail('O proprietário deve ser verificado.');
        } catch (\RuntimeException $exception) {
            $this->assertNull($event->fresh()->processed_at);
            $this->assertDatabaseMissing('pluggy_items', ['pluggy_item_id' => $itemId]);
        }
    }

    public function test_non_transactional_queue_is_rejected_without_recording_event(): void
    {
        config()->set('queue.default', 'sync');
        $this->postJson(route('pluggy.webhook', ['token' => 'webhook-secret']), [
            'event' => 'item/updated', 'eventId' => (string) \Illuminate\Support\Str::uuid(), 'itemId' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertStatus(503);
        $this->assertDatabaseCount('pluggy_webhook_events', 0);
    }

    public function test_database_queue_and_event_are_recorded_together(): void
    {
        $this->postJson(route('pluggy.webhook', ['token' => 'webhook-secret']), [
            'event' => 'item/updated', 'eventId' => (string) \Illuminate\Support\Str::uuid(), 'itemId' => (string) \Illuminate\Support\Str::uuid(), 'sensitive' => 'not retained',
        ])->assertOk();
        $this->assertDatabaseCount('pluggy_webhook_events', 1);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertStringNotContainsString('not retained', (string) \Illuminate\Support\Facades\DB::table('pluggy_webhook_events')->value('payload'));
    }

    public function test_out_of_order_item_events_consult_current_provider_state_and_are_idempotent(): void
    {
        $item = PluggyItem::factory()->create(['is_active' => true]);
        $event = PluggyWebhookEvent::create(['event_id' => (string) \Illuminate\Support\Str::uuid(), 'event' => 'item/deleted', 'pluggy_item_id' => $item->pluggy_item_id,
            'payload' => ['itemId' => $item->pluggy_item_id, 'clientUserId' => (string) $item->user_id], 'received_at' => now()]);
        $sync = \Mockery::mock(PluggySyncService::class);
        $sync->shouldReceive('sync')->once()->andReturn(['status' => 'success']);
        $pluggy = \Mockery::mock(PluggyService::class);
        (new ProcessPluggyWebhook($event->id))->handle($sync, $pluggy);
        (new ProcessPluggyWebhook($event->id))->handle($sync, $pluggy);
        $this->assertNotNull($event->fresh()->processed_at);
        $this->assertTrue((bool) $item->fresh()->is_active);
    }

    public function test_transaction_deletion_is_not_applied_without_provider_reconciliation(): void
    {
        $item = PluggyItem::factory()->create(['is_active' => true]);
        $event = PluggyWebhookEvent::create(['event_id' => (string) \Illuminate\Support\Str::uuid(), 'event' => 'transactions/deleted', 'pluggy_item_id' => $item->pluggy_item_id,
            'payload' => ['itemId' => $item->pluggy_item_id, 'transactionIds' => [(string) \Illuminate\Support\Str::uuid()]], 'received_at' => now()]);
        $sync = \Mockery::mock(PluggySyncService::class);
        $sync->shouldNotReceive('markTransactionsDeleted');
        $pluggy = \Mockery::mock(PluggyService::class);
        $pluggy->shouldReceive('getItem')->once()->andReturn(['status' => 'UPDATING']);
        try {
            (new ProcessPluggyWebhook($event->id))->handle($sync, $pluggy);
            $this->fail('A reconciliação deve aguardar o provedor.');
        } catch (\RuntimeException $exception) {
            $this->assertNull($event->fresh()->processed_at);
        }
    }

    public function test_deletion_checks_current_provider_before_removing_a_transaction(): void
    {
        $item = PluggyItem::factory()->create(['is_active' => true]);
        $account = \App\Models\PluggyAccount::create(['user_id' => $item->user_id, 'pluggy_connection_id' => $item->id,
            'pluggy_account_id' => (string) \Illuminate\Support\Str::uuid(), 'name' => 'Conta', 'type' => 'BANK', 'currency_code' => 'BRL', 'balance' => 0]);
        $transaction = \App\Models\PluggyTransaction::create(['user_id' => $item->user_id, 'pluggy_connection_id' => $item->id,
            'pluggy_account_id' => $account->id, 'pluggy_transaction_id' => (string) \Illuminate\Support\Str::uuid(),
            'description' => 'Teste', 'transaction_date' => '2026-01-01', 'amount' => 10, 'classification' => 'expense', 'status' => 'POSTED', 'affects_totals' => true]);
        $payload = ['itemId' => $item->pluggy_item_id, 'transactionIds' => [$transaction->pluggy_transaction_id]];
        $pluggy = \Mockery::mock(PluggyService::class);
        $pluggy->shouldReceive('getItem')->twice()->andReturn(['status' => 'UPDATED', 'executionStatus' => 'SUCCESS']);
        $pluggy->shouldReceive('isTransactionMissing')->twice()->andReturn(false, true);
        $sync = app(PluggySyncService::class);
        foreach ([false, true] as $removed) {
            $event = PluggyWebhookEvent::create(['event_id' => (string) \Illuminate\Support\Str::uuid(), 'event' => 'transactions/deleted', 'pluggy_item_id' => $item->pluggy_item_id, 'payload' => $payload, 'received_at' => now()]);
            (new ProcessPluggyWebhook($event->id))->handle($sync, $pluggy);
            $this->assertSame($removed, $transaction->fresh()->removed_at !== null);
        }
    }

    public function test_only_transaction_endpoint_404_confirms_missing(): void
    {
        config()->set('services.pluggy.client_id', 'fake-id');
        config()->set('services.pluggy.client_secret', 'fake-secret');
        config()->set('services.pluggy.base_url', 'https://pluggy.invalid');
        \Illuminate\Support\Facades\Http::fake([
            '*/auth' => \Illuminate\Support\Facades\Http::response(['apiKey' => 'fake-key'], 200),
            '*/transactions/*' => \Illuminate\Support\Facades\Http::response([], 404),
        ]);
        $this->assertTrue(app(PluggyService::class)->isTransactionMissing((string) \Illuminate\Support\Str::uuid()));
    }
}
