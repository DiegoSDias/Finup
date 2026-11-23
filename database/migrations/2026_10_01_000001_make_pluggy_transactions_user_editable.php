<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pluggy_transactions', function (Blueprint $table) {
            $table->timestamp('deleted_by_user_at')->nullable()->index()->after('removed_at');
            $table->boolean('user_modified_description')->default(false)->after('deleted_by_user_at');
            $table->boolean('user_modified_category')->default(false)->after('user_modified_description');
            $table->boolean('user_modified_date')->default(false)->after('user_modified_category');
        });
    }

    public function down(): void
    {
        Schema::table('pluggy_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'deleted_by_user_at',
                'user_modified_description',
                'user_modified_category',
                'user_modified_date',
            ]);
        });
    }
};
