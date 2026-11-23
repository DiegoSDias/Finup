<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pluggy_category_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('phrase', 150);
            $table->date('starts_on')->nullable();
            $table->date('ends_before')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'starts_on', 'ends_before']);
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('pluggy_category_rules')->exists()) {
            throw new RuntimeException('Reversão bloqueada: existem regras de categoria cadastradas.');
        }
        Schema::dropIfExists('pluggy_category_rules');
    }
};
