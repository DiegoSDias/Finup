<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TestEnvironmentSafetyTest extends TestCase
{
    public function test_only_the_in_memory_sqlite_connection_is_available(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertEmpty(config('database.connections.sqlite.url'));
        $this->assertSame(['sqlite'], array_keys(config('database.connections')));
    }

    public function test_guard_rejects_an_external_database_or_url(): void
    {
        $original = config('database');
        try {
            foreach ([
                ['database.default' => 'mysql'],
                ['database.connections.sqlite.database' => 'real.sqlite'],
                ['database.connections.sqlite.url' => 'sqlite:///real.sqlite'],
                ['database.connections.sqlite.driver' => 'mysql'],
            ] as $unsafe) {
                config($unsafe);
                try {
                    $this->assertIsolatedDatabaseConfiguration();
                    $this->fail('Uma configuração de banco insegura foi aceita.');
                } catch (RuntimeException $exception) {
                    $this->assertStringContainsString('SQLite em memória', $exception->getMessage());
                } finally {
                    config(['database' => $original]);
                }
            }
        } finally {
            config(['database' => $original]);
        }
    }

    public function test_unmocked_laravel_http_requests_are_blocked(): void
    {
        $this->expectException(RuntimeException::class);
        Http::get('https://example.invalid/');
    }
}
