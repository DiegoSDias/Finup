<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('manual_bank_id')->nullable()->constrained('manual_banks')->restrictOnDelete();
            $table->string('bank_status', 16)->nullable();
            $table->index(['user_id', 'manual_bank_id']);
            $table->string('payment_mode', 32)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        if (DB::table('expenses')->where(function ($query) {
            $query->whereNotNull('bank_status')->orWhereNotNull('manual_bank_id')
                ->orWhereNotIn('payment_mode', ['pix', 'cartao']);
        })->exists()) {
            throw new RuntimeException('Reversão bloqueada: existem dados de banco ou novas formas de pagamento em lançamentos.');
        }
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['manual_bank_id']);
            $table->dropIndex(['user_id', 'manual_bank_id']);
            $table->dropColumn(['manual_bank_id', 'bank_status']);
            $table->enum('payment_mode', ['pix', 'cartao'])->nullable()->default('cartao')->change();
        });
    }
};
