<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pluggy_transaction_rules', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'phrase', 'action']);
            $table->foreignId('pluggy_connection_id')->nullable()->constrained('pluggy_items')->restrictOnDelete();
            $table->date('ends_before')->nullable();
        });
    }
    public function down(): void
    {
        throw new RuntimeException('Reversão bloqueada: regras históricas devem ser preservadas.');
    }
};
