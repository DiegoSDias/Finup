<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pluggy_items', function (Blueprint $table): void {
            $table->string('display_name', 100)->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('pluggy_items')->whereNotNull('display_name')->exists()) {
            throw new RuntimeException('Reversão bloqueada: existem apelidos de conexões cadastrados.');
        }

        Schema::table('pluggy_items', fn (Blueprint $table) => $table->dropColumn('display_name'));
    }
};
