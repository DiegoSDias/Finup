<?php

namespace Tests\Feature;

use App\Jobs\SyncPluggyConnection;
use App\Models\PluggyItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PluggyConnectionsRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_all_schedules_only_own_active_connections_not_already_processing(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $first = PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => true, 'last_sync_status' => 'success']);
        $second = PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => true, 'last_sync_status' => null]);
        $running = PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => true, 'last_sync_status' => 'running']);
        $inactive = PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => false]);
        $other = PluggyItem::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('pluggy.connections.sync-all'))->assertRedirect()->assertSessionHas('success');

        Queue::assertPushed(SyncPluggyConnection::class, 2);
        foreach ([$first, $second] as $item) {
            Queue::assertPushed(SyncPluggyConnection::class, fn ($job) => $job->connectionId === $item->id && $job->requestRemoteSync);
            $this->assertSame('queued', $item->fresh()->last_sync_status);
        }
        $this->assertSame('running', $running->fresh()->last_sync_status);
        $this->assertNull($inactive->fresh()->last_sync_status);
        $this->assertNull($other->fresh()->last_sync_status);

        $this->actingAs($user)->post(route('pluggy.connections.sync-all'));
        Queue::assertPushed(SyncPluggyConnection::class, 2);
    }

    public function test_connection_page_displays_own_status_and_refresh_controls(): void
    {
        $user = User::factory()->create();
        PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => true, 'last_sync_status' => 'queued', 'connector_name' => 'Meu banco']);
        PluggyItem::factory()->create(['is_active' => true, 'connector_name' => 'Banco de outra pessoa']);

        $this->actingAs($user)->get(route('pluggy.connections'))
            ->assertOk()->assertSee('Sincronizar todos')->assertSee('Aguardando na fila')
            ->assertSee('data-sync-status="queued"', false)->assertSee('connection-overview')
            ->assertDontSee('Banco de outra pessoa');
    }

    public function test_sync_all_requires_authentication(): void
    {
        $this->post(route('pluggy.connections.sync-all'))->assertRedirect(route('login'));
    }

    public function test_owner_can_set_and_clear_connection_name_without_changing_provider_name(): void
    {
        $user = User::factory()->create();
        $connection = PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => true, 'connector_name' => 'MyPluggy']);
        $other = User::factory()->create();

        $this->actingAs($other)->patch(route('pluggy.connections.name', $connection), ['display_name' => 'Outro'])->assertNotFound();
        $this->actingAs($user)->patch(route('pluggy.connections.name', $connection), ['display_name' => ' Mercado Pago '])->assertSessionHasNoErrors();
        $this->assertSame('Mercado Pago', $connection->fresh()->display_label);
        $this->assertSame('MyPluggy', $connection->fresh()->connector_name);
        $this->withoutVite()->get(route('pluggy.connections'))->assertSee('Mercado Pago');
        $this->patch(route('pluggy.connections.name', $connection), ['display_name' => str_repeat('a', 101)])->assertSessionHasErrors('display_name');
        $this->patch(route('pluggy.connections.name', $connection), ['display_name' => ''])->assertSessionHasNoErrors();
        $this->assertSame('MyPluggy', $connection->fresh()->display_label);
    }
}
