<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->boolean('posting_enabled')->default(false);
            $table->integer('default_alert_limit')->default(5);
            $table->text('company_notes')->nullable();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('code')->nullable();
            $table->decimal('opening_usd', 14, 2)->default(0);
            $table->decimal('opening_syp', 14, 2)->default(0);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->string('code')->nullable();
            $table->string('category')->nullable();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('operation_subtype')->default('cash');
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('paid_amount_usd', 14, 2)->default(0);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->string('voucher_number')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('affects_balance')->default(true);
            $table->string('document_ref')->nullable();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('item_location_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('opening_qty', 12, 2)->default(0);
            $table->decimal('opening_cost_usd', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['location_id', 'item_id']);
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->date('date');
            $table->string('number');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('qty', 12, 2);
            $table->foreignId('from_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('to_location_id')->constrained('locations')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('qty', 12, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->decimal('total_usd', 14, 2);
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type'); // asset, liability, equity, revenue, expense
            $table->string('classification')->nullable();
            $table->decimal('opening_debit', 14, 2)->default(0);
            $table->decimal('opening_credit', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['shop_id', 'code']);
        });

        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->string('role');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
            $table->unique(['shop_id', 'role']);
        });

        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type')->nullable();
            $table->decimal('cost_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->date('date');
            $table->string('document_number');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_records', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('date');
            $table->string('voucher_number')->nullable();
            $table->string('month');
            $table->decimal('basic', 14, 2)->default(0);
            $table->decimal('extra', 14, 2)->default(0);
            $table->decimal('deduction', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 12, 2)->default(14500);
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->string('payment_method')->default('نقدي');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('cash_movement_id')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_movements', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->date('date');
            $table->string('voucher_number')->nullable();
            $table->string('type'); // advance, salary_payment, settlement
            $table->string('month')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 12, 2)->default(14500);
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->string('payment_method')->default('نقدي');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('cash_movement_id')->nullable();
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('partner_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained('partners')->restrictOnDelete();
            $table->date('date');
            $table->string('voucher_number')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 12, 2)->default(14500);
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->string('method')->default('نقدي');
            $table->string('nature')->default('مسحوب شخصي نهائي');
            $table->string('classification_status')->default('مصنف');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('cash_movement_id')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->date('date');
            $table->string('voucher_number')->nullable();
            $table->string('expense_type');
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 12, 2)->default(14500);
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->string('payment_method')->default('نقدي');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('cash_movement_id')->nullable();
            $table->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->string('type'); // receipt, payment
            $table->string('number');
            $table->date('date');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('beneficiary')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 12, 2)->default(14500);
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->string('payment_method')->default('نقدي');
            $table->string('original_ref')->nullable();
            $table->boolean('affects_balance')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('cash_movement_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('partner_withdrawals');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('employee_movements');
        Schema::dropIfExists('salary_records');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('transaction_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('item_location_stocks');
        Schema::dropIfExists('locations');

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropColumn(['voucher_number', 'source_type', 'source_id', 'affects_balance', 'document_ref']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['operation_subtype', 'paid_amount', 'paid_amount_usd', 'location_id', 'cost_center_id']);
        });
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['code', 'category']);
        });
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['code', 'opening_usd', 'opening_syp']);
        });
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['posting_enabled', 'default_alert_limit', 'company_notes']);
        });
    }
};
