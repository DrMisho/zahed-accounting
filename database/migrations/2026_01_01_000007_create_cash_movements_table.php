<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 6. cash_movements (صندوق): id, shop_id, date, type, contact_id, amount, currency, exchange_rate, amount_usd, notes
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->date('date');
            $table->enum('type', ['receipt', 'payment', 'expense', 'salary', 'withdrawal', 'opening']);
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('category')->nullable();
            $table->decimal('amount', 14, 2);
            $table->enum('currency', ['USD', 'SYP'])->default('USD');
            $table->decimal('exchange_rate', 12, 2)->default(14500.00);
            $table->decimal('amount_usd', 14, 2); // المعادل المخزن بالدولار
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
