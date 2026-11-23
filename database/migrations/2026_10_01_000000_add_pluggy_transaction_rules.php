<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pluggy_transactions', function (Blueprint $table) {
            $table->string('exclusion_reason', 80)->nullable()->index()->after('affects_totals');
        });

        Schema::create('pluggy_transaction_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phrase', 150);
            $table->string('action', 40);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'phrase', 'action']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pluggy_transaction_rules');

        Schema::table('pluggy_transactions', function (Blueprint $table) {
            $table->dropColumn('exclusion_reason');
        });
    }
};
