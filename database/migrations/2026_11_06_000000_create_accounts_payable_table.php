<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_payable', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 12, 2);
            $table->date('due_date');
            $table->string('status', 16)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('accounts_payable') && \Illuminate\Support\Facades\DB::table('accounts_payable')->exists()) {
            throw new RuntimeException('Reversão bloqueada: existem contas a pagar cadastradas.');
        }

        Schema::dropIfExists('accounts_payable');
    }
};
