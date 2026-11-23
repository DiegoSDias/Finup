<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        $this->assertIsolatedDatabaseConfiguration();
        // Remove named real connections too, before RefreshDatabase runs.
        $app['config']->set('database.connections', [
            'sqlite' => $app['config']->get('database.connections.sqlite'),
        ]);
        Http::preventStrayRequests();
        Mail::fake();

        return $app;
    }

    protected function assertIsolatedDatabaseConfiguration(): void
    {
        if (config('app.env') !== 'testing'
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.driver') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || config('database.connections.sqlite.url')) {
            throw new RuntimeException('Testes exigem ambiente testing e SQLite em memória, sem DB_URL. Verifique o cache de configuração.');
        }
    }
}
