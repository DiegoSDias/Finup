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
            $table->foreignId('recurring_expense_id')->nullable()->constrained()->nullOnDelete();
            $table->date('recurrence_occurrence_date')->nullable();
            $table->boolean('recurrence_is_override')->default(false);
            $table->string('recurrence_status', 16)->nullable();
            $table->unique(['recurring_expense_id', 'recurrence_occurrence_date'], 'expenses_recurrence_occurrence_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('expenses')->whereNotNull('recurring_expense_id')->exists()) {
            throw new \RuntimeException('Reversão bloqueada: existem ocorrências recorrentes. Exporte ou remova esses dados antes de reverter a migration.');
        }

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique('expenses_recurrence_occurrence_unique');
            $table->dropForeign(['recurring_expense_id']);
            $table->dropColumn([
                'recurring_expense_id',
                'recurrence_occurrence_date',
                'recurrence_is_override',
                'recurrence_status',
            ]);
        });
    }
};
