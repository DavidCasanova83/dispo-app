<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->enum('relevance', ['very', 'rather', 'medium', 'low'])->nullable()->after('remarks');
            $table->json('content_to_add')->nullable()->after('relevance');
            $table->text('content_to_add_details')->nullable()->after('content_to_add');
            $table->enum('content_to_remove', ['no', 'yes', 'dont_know'])->nullable()->after('content_to_add_details');
            $table->text('content_to_remove_details')->nullable()->after('content_to_remove');
            $table->enum('visitor_perspective', ['yes', 'partial', 'no'])->nullable()->after('content_to_remove_details');
            $table->text('visitor_unanswered')->nullable()->after('visitor_perspective');
            $table->unsignedTinyInteger('rating')->nullable()->after('visitor_unanswered');
            $table->text('suggestions')->nullable()->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('verification_reviews', function (Blueprint $table) {
            $table->dropColumn([
                'relevance',
                'content_to_add',
                'content_to_add_details',
                'content_to_remove',
                'content_to_remove_details',
                'visitor_perspective',
                'visitor_unanswered',
                'rating',
                'suggestions',
            ]);
        });
    }
};
