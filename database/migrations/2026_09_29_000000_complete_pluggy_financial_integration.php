<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pluggy_items', function (Blueprint $table) {
            $table->unsignedInteger('connector_id')->nullable()->index()->after('pluggy_item_id');
            $table->string('connector_name')->nullable()->after('connector_id');
            $table->string('connector_image_url', 1_000)->nullable()->after('connector_name');
            $table->string('status', 40)->nullable()->index()->after('connector_image_url');
            $table->string('execution_status', 40)->nullable()->index()->after('status');
            $table->timestamp('last_sync_started_at')->nullable()->after('execution_status');
            $table->timestamp('last_synced_at')->nullable()->after('last_sync_started_at');
            $table->string('last_sync_status', 40)->nullable()->after('last_synced_at');
            $table->boolean('is_active')->default(true)->index()->after('last_sync_status');
        });

        Schema::create('pluggy_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pluggy_connection_id')->constrained('pluggy_items')->cascadeOnDelete();
            $table->uuid('pluggy_account_id');
            $table->string('name');
            $table->string('type', 20);
            $table->string('subtype', 60)->nullable();
            $table->string('number_last_four', 4)->nullable();
            $table->string('currency_code', 3)->default('BRL');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('owner_document_hash', 64)->nullable()->index();
            $table->string('account_number_hash', 64)->nullable()->index();
            $table->string('routing_number_hash', 64)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['pluggy_connection_id', 'pluggy_account_id']);
            $table->index(['user_id', 'is_active']);
        });

        Schema::create('pluggy_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pluggy_connection_id')->constrained('pluggy_items')->cascadeOnDelete();
            $table->foreignId('pluggy_account_id')->constrained('pluggy_accounts')->cascadeOnDelete();
            $table->uuid('pluggy_transaction_id')->unique();
            $table->string('provider_id', 191)->nullable();
            $table->string('provider_code', 191)->nullable();
            $table->string('description', 500);
            $table->text('description_raw')->nullable();
            $table->date('transaction_date')->index();
            $table->timestamp('transaction_at')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency_code', 3)->default('BRL');
            $table->string('direction', 10)->nullable();
            $table->string('status', 20)->nullable()->index();
            $table->string('operation_type', 80)->nullable()->index();
            $table->string('classification', 40)->index();
            $table->boolean('affects_totals')->default(false)->index();
            $table->string('payment_method', 20)->nullable()->index();
            $table->string('pluggy_category_id', 80)->nullable();
            $table->string('pluggy_category_name', 120)->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category_source', 20)->default('pluggy');
            $table->boolean('is_installment')->default(false);
            $table->unsignedTinyInteger('current_installment')->nullable();
            $table->unsignedTinyInteger('total_installments')->nullable();
            $table->date('purchase_date')->nullable();
            $table->json('payment_data')->nullable();
            $table->json('credit_card_metadata')->nullable();
            $table->json('raw_payload')->nullable();
            $table->boolean('is_internal_transfer')->default(false)->index();
            $table->foreignId('paired_transaction_id')->nullable()->constrained('pluggy_transactions')->nullOnDelete();
            $table->timestamp('removed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'provider_id']);
            $table->index(['user_id', 'transaction_date']);
            $table->index(['pluggy_account_id', 'transaction_date']);
        });

        Schema::create('pluggy_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('event', 80)->index();
            $table->uuid('pluggy_item_id')->nullable()->index();
            $table->json('payload');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pluggy_webhook_events');
        Schema::dropIfExists('pluggy_transactions');
        Schema::dropIfExists('pluggy_accounts');

        Schema::table('pluggy_items', function (Blueprint $table) {
            $table->dropColumn([
                'connector_id', 'connector_name', 'connector_image_url', 'status', 'execution_status',
                'last_sync_started_at', 'last_synced_at', 'last_sync_status', 'is_active',
            ]);
        });
    }
};
