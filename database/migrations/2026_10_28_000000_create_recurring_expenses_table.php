<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('manual_bank_id')->nullable()->constrained('manual_banks')->restrictOnDelete();
            $table->string('bank_status', 16)->nullable();
            $table->string('payment_mode', 32)->nullable();
            $table->string('description', 255)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('frequency', 16);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_active', 'start_date']);
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('recurring_expenses')->exists()) {
            throw new \RuntimeException('Reversão bloqueada: existem recorrências ou ocorrências recorrentes. Exporte ou remova esses dados antes de reverter a migration.');
        }

        Schema::dropIfExists('recurring_expenses');
    }
};
