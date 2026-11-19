<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 5. transactions (فواتير): id, shop_id, date, type, payment_type, contact_id, item_id, qty, unit_price, total, currency, exchange_rate, total_usd
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->string('invoice_number')->index();
            $table->date('date');
            $table->enum('type', ['sale', 'purchase', 'sale_return', 'purchase_return']);
            $table->enum('payment_type', ['cash', 'credit']);
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('restrict');
            $table->foreignId('item_id')->constrained('items')->onDelete('restrict');
            $table->decimal('qty', 10, 2);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('total', 14, 2);
            $table->enum('currency', ['USD', 'SYP'])->default('USD');
            $table->decimal('exchange_rate', 12, 2)->default(14500.00);
            $table->decimal('total_usd', 14, 2); // المعادل المخزن بالدولار
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
