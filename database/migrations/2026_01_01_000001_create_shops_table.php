<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. shops: id, name, subscription_status, end_date, exchange_rate
        Schema::create('shops', function (Blueprint $table) {
            $table->string('id')->primary(); // e.g. shop_amana
            $table->string('name');
            $table->enum('subscription_status', ['active', 'expired', 'suspended'])->default('active');
            $table->date('end_date');
            $table->decimal('exchange_rate', 12, 2)->default(14500.00);
            $table->string('owner_name')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('plan_price_usd', 8, 2)->default(35.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
