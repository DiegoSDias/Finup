<?php

namespace Tests\Feature;

use App\Jobs\SyncPluggyConnection;
use App\Models\PluggyAccount;
use App\Models\PluggyItem;
use App\Models\PluggyTransaction;
use App\Models\User;
use App\Models\Category;
use App\Services\Pluggy\PluggyService;
use App\Services\Pluggy\PluggySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;
use Illuminate\Support\Str;

class PluggySyncStabilityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $user = User::factory()->create();
        $item = PluggyItem::factory()->create(['user_id' => $user->id, 'is_active' => true]);
        $account = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Conta', 'type' => 'BANK', 'currency_code' => 'BRL', 'balance' => 20, 'is_active' => true]);
        $transaction = PluggyTransaction::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => $account->id,
            'pluggy_transaction_id' => (string) Str::uuid(), 'description' => 'Original', 'transaction_date' => '2026-01-01', 'amount' => 10,
            'status' => 'POSTED', 'classification' => 'expense', 'affects_totals' => true]);
        return [$user, $item, $account, $transaction];
    }

    private function responseError(int $status): RequestException
    {
        return new RequestException(new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response($status, [], '{"secret":"conteudo-sensivel"}')));
    }

    public function test_item_and_account_or_transaction_404_preserve_data(): void
    {
        foreach (['item', 'accounts', 'transactions'] as $stage) {
            [$user, $item, $account, $transaction] = $this->fixture();
            $service = Mockery::mock(PluggyService::class);
            if ($stage === 'item') {
                $service->shouldReceive('getItem')->once()->andThrow(new \App\Services\Pluggy\PluggyItemNotFoundException());
            } else {
                $service->shouldReceive('getItem')->once()->andReturn(['status' => 'UPDATED', 'executionStatus' => 'SUCCESS']);
                if ($stage === 'accounts') {
                    $service->shouldReceive('getAccountsForItem')->once()->andThrow($this->responseError(404));
                } else {
                    $service->shouldReceive('getAccountsForItem')->once()->andReturn([['id' => $account->pluggy_account_id]]);
                    $service->shouldReceive('getTransactions')->once()->andThrow($this->responseError(404));
                }
            }
            $this->app->instance(PluggyService::class, $service);
            $result = app(PluggySyncService::class)->sync($item);
            $this->assertSame($stage === 'item' ? 'needs_reconnection' : 'failed', $result['status']);
            $this->assertTrue($item->fresh()->is_active);
            $this->assertTrue($account->fresh()->is_active);
            $this->assertNull($transaction->fresh()->removed_at);
            $this->assertTrue((bool) $transaction->fresh()->affects_totals);
            $this->assertStringNotContainsString('conteudo-sensivel', (string) $item->fresh()->last_error);
        }
    }

    public function test_provider_states_preserve_existing_data(): void
    {
        foreach ([
            [['status' => 'UPDATING', 'executionStatus' => 'LOGIN_IN_PROGRESS'], 'awaiting_provider'],
            [['status' => 'WAITING_USER_INPUT'], 'needs_action'],
            [['status' => 'LOGIN_ERROR', 'executionStatus' => 'INVALID_CREDENTIALS'], 'needs_reconnection'],
            [['status' => 'UPDATED', 'executionStatus' => 'PARTIAL_SUCCESS'], 'partial'],
            [['status' => 'OUTDATED', 'executionStatus' => 'ERROR'], 'failed'],
        ] as [$payload, $expected]) {
            [, $item, $account, $transaction] = $this->fixture();
            $this->mock(PluggyService::class)->shouldReceive('getItem')->once()->andReturn($payload);
            $this->assertSame($expected, app(PluggySyncService::class)->sync($item)['status']);
            $this->assertTrue($account->fresh()->is_active);
            $this->assertTrue((bool) $transaction->fresh()->affects_totals);
            $this->app->forgetInstance(PluggyService::class);
        }
    }

    public function test_network_failure_and_partial_fetch_do_not_overwrite_data(): void
    {
        [, $item, $account, $transaction] = $this->fixture();
        $api = Mockery::mock(PluggyService::class);
        $api->shouldReceive('getItem')->once()->andReturn(['status' => 'UPDATED', 'executionStatus' => 'SUCCESS']);
        $api->shouldReceive('getAccountsForItem')->once()->andReturn([['id' => $account->pluggy_account_id, 'balance' => 999], ['id' => 'account-2']]);
        $api->shouldReceive('getTransactions')->with($account->pluggy_account_id)->once()->andReturn([]);
        $api->shouldReceive('getTransactions')->with('account-2')->once()->andThrow(new ConnectionException('sensitive detail'));
        $this->app->instance(PluggyService::class, $api);
        try {
            app(PluggySyncService::class)->sync($item);
            $this->fail('A falha de rede deveria permitir repetição.');
        } catch (\RuntimeException $exception) {
            $this->assertStringNotContainsString('sensitive detail', $exception->getMessage());
        }
        $this->assertEquals(20, $account->fresh()->balance);
        $this->assertSame(1, PluggyAccount::where('user_id', $item->user_id)->count());
        $this->assertTrue((bool) $transaction->fresh()->affects_totals);
        $this->assertSame('failed', $item->fresh()->last_sync_status);
    }

    public function test_http_authentication_failure_is_terminal_and_server_failure_is_retryable(): void
    {
        foreach ([401, 403, 500] as $code) {
            [, $item, $account, $transaction] = $this->fixture();
            $api = Mockery::mock(PluggyService::class);
            $api->shouldReceive('getItem')->once()->andThrow($this->responseError($code));
            $this->app->instance(PluggyService::class, $api);
            try {
                $result = app(PluggySyncService::class)->sync($item);
                $this->assertNotSame(500, $code, 'Erro de servidor deveria permitir repetição.');
                $this->assertSame('failed', $result['status']);
            } catch (\RuntimeException $exception) {
                $this->assertSame(500, $code);
                $this->assertStringNotContainsString('conteudo-sensivel', $exception->getMessage());
            }
            $this->assertSame('failed', $item->fresh()->last_sync_status);
            $this->assertTrue($account->fresh()->is_active);
            $this->assertTrue((bool) $transaction->fresh()->affects_totals);
        }
    }

    public function test_404_in_authentication_is_not_mistaken_for_missing_item(): void
    {
        [, $item, $account, $transaction] = $this->fixture();
        config()->set('services.pluggy.client_id', 'fake-id');
        config()->set('services.pluggy.client_secret', 'fake-secret');
        config()->set('services.pluggy.base_url', 'https://pluggy.invalid');
        Http::fake(['*' => Http::response([], 404)]);
        $this->assertSame('failed', app(PluggySyncService::class)->sync($item)['status']);
        $this->assertTrue($account->fresh()->is_active);
        $this->assertTrue((bool) $transaction->fresh()->affects_totals);
    }

    public function test_404_on_item_endpoint_is_distinguished_from_authentication(): void
    {
        [, $item, $account, $transaction] = $this->fixture();
        config()->set('services.pluggy.client_id', 'fake-id');
        config()->set('services.pluggy.client_secret', 'fake-secret');
        config()->set('services.pluggy.base_url', 'https://pluggy.invalid');
        Http::fake([
            '*/auth' => Http::response(['apiKey' => 'fake-key'], 200),
            '*/items/*' => Http::response([], 404),
        ]);
        $this->assertSame('needs_reconnection', app(PluggySyncService::class)->sync($item)['status']);
        $this->assertTrue($account->fresh()->is_active);
        $this->assertTrue((bool) $transaction->fresh()->affects_totals);
    }

    public function test_empty_account_response_does_not_deactivate_existing_accounts(): void
    {
        [, $item, $account, $transaction] = $this->fixture();
        $api = Mockery::mock(PluggyService::class);
        $api->shouldReceive('getItem')->once()->andReturn(['status' => 'UPDATED', 'executionStatus' => 'SUCCESS']);
        $api->shouldReceive('getAccountsForItem')->once()->andReturn([]);
        $this->app->instance(PluggyService::class, $api);
        $this->assertSame('partial', app(PluggySyncService::class)->sync($item)['status']);
        $this->assertTrue($account->fresh()->is_active);
        $this->assertTrue((bool) $transaction->fresh()->affects_totals);
    }

    public function test_duplicate_jobs_and_overlapping_sync_are_prevented(): void
    {
        [, $item] = $this->fixture();
        config()->set('queue.default', 'database');
        SyncPluggyConnection::dispatch($item->id);
        SyncPluggyConnection::dispatch($item->id, true);
        $this->assertSame(1, DB::table('jobs')->count());
        $lock = Cache::store('database')->lock('pluggy-sync:'.$item->id, 300);
        $this->assertTrue($lock->get());
        try {
            app(PluggySyncService::class)->sync($item);
            $this->fail('A sincronização simultânea deveria ser impedida.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('sincronização em andamento', $exception->getMessage());
        } finally {
            $lock->release();
        }
    }

    public function test_successful_repeated_sync_preserves_user_edits_and_local_deletion(): void
    {
        [$user, $item, $account, $existing] = $this->fixture();
        $category = Category::create(['user_id' => $user->id, 'name' => 'Escolhida', 'type' => 'expense']);
        $existing->update(['description' => 'Descrição editada', 'transaction_date' => '2026-02-01', 'category_id' => $category->id,
            'category_source' => 'user', 'user_modified_description' => true, 'user_modified_date' => true, 'user_modified_category' => true]);
        $deletedId = (string) Str::uuid();
        $deleted = PluggyTransaction::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => $account->id,
            'pluggy_transaction_id' => $deletedId, 'description' => 'Removida', 'transaction_date' => '2026-01-01', 'amount' => 2,
            'status' => 'POSTED', 'classification' => 'expense', 'affects_totals' => false, 'deleted_by_user_at' => now()]);
        $api = Mockery::mock(PluggyService::class);
        $api->shouldReceive('getItem')->twice()->andReturn(['status' => 'UPDATED', 'executionStatus' => 'SUCCESS']);
        $api->shouldReceive('getAccountsForItem')->twice()->andReturn([['id' => $account->pluggy_account_id, 'currencyCode' => 'BRL', 'balance' => 30]]);
        $api->shouldReceive('getTransactions')->twice()->andReturn([
            ['id' => $existing->pluggy_transaction_id, 'description' => 'Mudança remota', 'date' => '2026-03-01', 'type' => 'DEBIT', 'amount' => 10, 'status' => 'POSTED', 'currencyCode' => 'BRL'],
            ['id' => $deletedId, 'description' => 'Removida', 'date' => '2026-01-01', 'type' => 'DEBIT', 'amount' => 2, 'status' => 'POSTED', 'currencyCode' => 'BRL'],
        ]);
        $this->app->instance(PluggyService::class, $api);
        $this->assertSame('success', app(PluggySyncService::class)->sync($item)['status']);
        $this->assertSame('success', app(PluggySyncService::class)->sync($item)['status']);
        $this->assertSame(2, PluggyTransaction::where('user_id', $user->id)->count());
        $this->assertSame('Descrição editada', $existing->fresh()->description);
        $this->assertSame('2026-02-01', $existing->fresh()->transaction_date->format('Y-m-d'));
        $this->assertSame($category->id, $existing->fresh()->category_id);
        $this->assertNotNull($deleted->fresh()->deleted_by_user_at);
        $this->assertFalse((bool) $deleted->fresh()->affects_totals);
        $this->actingAs($user)->withoutVite()->get(route('pluggy.connections'))->assertOk()->assertSee('Sincronização concluída');
    }

    public function test_conflicting_provider_id_in_another_account_fails_without_moving_transactions(): void
    {
        [$user, $item, $account, $existing] = $this->fixture();
        $existing->update(['provider_id' => 'same-provider-id']);
        $otherAccount = PluggyAccount::create(['user_id' => $user->id, 'pluggy_connection_id' => $item->id, 'pluggy_account_id' => (string) Str::uuid(), 'name' => 'Outra', 'type' => 'BANK', 'currency_code' => 'BRL', 'is_active' => true]);
        try {
            app(\App\Services\Pluggy\PluggyTransactionImporter::class)->import($item, $otherAccount, [[
                'id' => (string) Str::uuid(), 'providerId' => 'same-provider-id', 'date' => '2026-01-01', 'amount' => 10, 'type' => 'DEBIT', 'currencyCode' => 'BRL',
            ]]);
            $this->fail('A identificação ambígua deveria ser rejeitada.');
        } catch (\LogicException $exception) {
            $this->assertSame($account->id, $existing->fresh()->pluggy_account_id);
            $this->assertSame(1, PluggyTransaction::where('user_id', $user->id)->count());
        }
    }

    public function test_failure_during_database_import_rolls_back_changes_to_all_accounts(): void
    {
        [, $item, $account, $transaction] = $this->fixture();
        $api = Mockery::mock(PluggyService::class);
        $api->shouldReceive('getItem')->once()->andReturn(['status' => 'UPDATED', 'executionStatus' => 'SUCCESS']);
        $api->shouldReceive('getAccountsForItem')->once()->andReturn([
            ['id' => $account->pluggy_account_id, 'balance' => 999], ['id' => (string) Str::uuid()],
        ]);
        $api->shouldReceive('getTransactions')->twice()->andReturn([]);
        $importer = Mockery::mock(\App\Services\Pluggy\PluggyTransactionImporter::class);
        $importer->shouldReceive('import')->once()->ordered()->andReturn(['created' => 0, 'updated' => 0]);
        $importer->shouldReceive('import')->once()->ordered()->andThrow(new \LogicException('erro simulado'));
        $this->app->instance(PluggyService::class, $api);
        $this->app->instance(\App\Services\Pluggy\PluggyTransactionImporter::class, $importer);
        try {
            app(PluggySyncService::class)->sync($item);
            $this->fail('A importação deveria falhar.');
        } catch (\RuntimeException $exception) {
            $this->assertEquals(20, $account->fresh()->balance);
            $this->assertSame(1, PluggyAccount::where('user_id', $item->user_id)->count());
            $this->assertTrue((bool) $transaction->fresh()->affects_totals);
            $this->assertSame('failed', $item->fresh()->last_sync_status);
        }
    }

    public function test_connection_screen_shows_each_sync_state(): void
    {
        [$user, $item] = $this->fixture();
        $this->actingAs($user)->withoutVite();
        foreach ([
            'queued' => 'Aguardando na fila', 'running' => 'Sincronização em andamento',
            'awaiting_provider' => 'Aguardando o provedor', 'success' => 'Sincronização concluída',
            'failed' => 'Sincronização falhou', 'partial' => 'Atualização parcial no provedor',
            'needs_reconnection' => 'Conexão precisa de revisão', 'needs_action' => 'A instituição solicita uma ação',
        ] as $status => $label) {
            $item->update(['last_sync_status' => $status]);
            $this->get(route('pluggy.connections'))->assertOk()->assertSee($label);
        }
    }
}
