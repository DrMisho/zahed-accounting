<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 3. items: id, shop_id, item_name, unit, alert_limit
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('shop_id');
            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
            $table->string('item_name');
            $table->string('unit')->default('قطعة');
            $table->integer('alert_limit')->default(5);
            $table->decimal('opening_qty', 10, 2)->default(0);
            $table->decimal('opening_cost_usd', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['shop_id', 'item_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
