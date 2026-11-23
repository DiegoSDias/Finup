<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pluggy_transactions', function (Blueprint $table): void {
            $table->boolean('phrase_rules_restored')->default(false);
        });
    }

    public function down(): void
    {
        if (DB::table('pluggy_transactions')->where('phrase_rules_restored', true)->exists()) {
            throw new RuntimeException('Reversão bloqueada: existem restaurações individuais que devem ser preservadas.');
        }

        Schema::table('pluggy_transactions', function (Blueprint $table): void {
            $table->dropColumn('phrase_rules_restored');
        });
    }
};
