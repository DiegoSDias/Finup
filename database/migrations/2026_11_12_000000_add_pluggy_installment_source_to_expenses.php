<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('pluggy_installment_source_id')->nullable()->constrained('pluggy_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('expenses')->whereNotNull('pluggy_installment_source_id')->exists()) {
            throw new RuntimeException('Reversão bloqueada: existem parcelas manuais vinculadas.');
        }
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pluggy_installment_source_id');
        });
    }
};
