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
        Schema::create('image_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();

            // Type de client
            $table->enum('customer_type', ['professionnel', 'particulier']);

            // Langue
            $table->enum('language', ['francais', 'anglais', 'neerlandais', 'italien', 'allemand', 'espagnol'])->default('francais');

            // Informations client
            $table->string('company')->nullable(); // Société (seulement si professionnel)
            $table->enum('civility', ['mr', 'mme', 'autre']);
            $table->string('last_name');
            $table->string('first_name');

            // Adresse
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('postal_code');
            $table->string('city');
            $table->string('country')->default('France');

            // Contact
            $table->string('email');
            $table->string('phone_country_code')->nullable();
            $table->string('phone_number')->nullable();

            // Statut
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');

            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index
            $table->index('order_number');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_orders');
    }
};
