<?php

namespace App\Jobs;

use App\Models\PluggyItem;
use App\Services\Pluggy\PluggySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class SyncPluggyConnection implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];
    public int $timeout = 240;
    public int $uniqueFor = 1800;

    public function uniqueId(): string
    {
        return (string) $this->connectionId;
    }

    public function uniqueVia(): Repository
    {
        return Cache::store('database');
    }

    public function __construct(public int $connectionId, public bool $requestRemoteSync = false)
    {
    }

    public function handle(PluggySyncService $syncService): void
    {
        $connection = PluggyItem::query()->where('is_active', true)->find($this->connectionId);

        if ($connection) {
            $syncService->sync($connection, $this->requestRemoteSync);
        }
    }
}
