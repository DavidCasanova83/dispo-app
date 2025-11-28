<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('image_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_order_id')->constrained('image_orders')->onDelete('cascade');
            $table->foreignId('image_id')->constrained('images')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();

            // Index
            $table->index('image_order_id');
            $table->index('image_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_order_items');
    }
};
