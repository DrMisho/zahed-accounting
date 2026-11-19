<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('subtotal', 14, 2)->default(0)->after('unit_price');
            $table->decimal('subtotal_usd', 14, 2)->default(0)->after('subtotal');
            $table->decimal('discount', 14, 2)->default(0)->after('subtotal_usd');
            $table->decimal('discount_usd', 14, 2)->default(0)->after('discount');
        });

        DB::table('transactions')->update([
            'subtotal' => DB::raw('total'),
            'subtotal_usd' => DB::raw('total_usd'),
        ]);

        Schema::create('currency_exchanges', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->date('date');
            $table->string('reference_number')->nullable();
            $table->string('direction'); // syp_to_usd, usd_to_syp
            $table->string('from_currency', 10); // SYP, USD
            $table->string('to_currency', 10); // USD, SYP
            $table->decimal('from_amount', 16, 2);
            $table->decimal('exchange_rate', 12, 2);
            $table->decimal('to_amount', 16, 2);
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->unsignedBigInteger('out_cash_movement_id')->nullable();
            $table->unsignedBigInteger('in_cash_movement_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'date']);
        });

        Schema::create('shipping_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->date('date');
            $table->string('voucher_number')->nullable();
            $table->string('carrier_name')->nullable();
            $table->string('shipping_type')->default('شحن مشتريات');
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate', 12, 2)->default(14500);
            $table->decimal('amount_usd', 14, 2)->default(0);
            $table->string('payment_method')->default('نقدي');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('cash_movement_id')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'date']);
            $table->index(['shop_id', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_expenses');
        Schema::dropIfExists('currency_exchanges');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'subtotal_usd', 'discount', 'discount_usd']);
        });
    }
};
