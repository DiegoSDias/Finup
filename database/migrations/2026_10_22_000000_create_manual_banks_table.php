<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->unique(['user_id', 'name']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::table('manual_banks')->exists()) {
            throw new RuntimeException('Reversão bloqueada: existem bancos manuais cadastrados.');
        }
        Schema::drop('manual_banks');
    }
};
