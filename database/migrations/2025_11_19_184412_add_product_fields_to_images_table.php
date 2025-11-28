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
        Schema::table('images', function (Blueprint $table) {
            $table->integer('quantity_available')->nullable()->after('thumbnail_path');
            $table->integer('max_order_quantity')->nullable()->after('quantity_available');
            $table->boolean('print_available')->default(false)->after('max_order_quantity');
            $table->integer('edition_year')->nullable()->after('print_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn([
                'quantity_available',
                'max_order_quantity',
                'print_available',
                'edition_year',
            ]);
        });
    }
};
