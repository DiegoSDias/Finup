<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payable_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_payable_id')->constrained('accounts_payable')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manual_expense_id')->nullable()->constrained('expenses')->restrictOnDelete();
            $table->foreignId('pluggy_transaction_id')->nullable()->constrained('pluggy_transactions')->restrictOnDelete();
            $table->date('paid_on');
            $table->timestamps();

            $table->unique('account_payable_id');
            $table->unique('manual_expense_id');
            $table->unique('pluggy_transaction_id');
            $table->index(['user_id', 'paid_on']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('payable_settlements') && \Illuminate\Support\Facades\DB::table('payable_settlements')->exists()) {
            throw new RuntimeException('Reversão bloqueada: existem liquidações de contas a pagar.');
        }

        Schema::dropIfExists('payable_settlements');
    }
};
