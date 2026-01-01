<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // In case a previous run failed after creating the table (e.g. long index name on MySQL)
        Schema::dropIfExists('class_room_revenue_category');

        Schema::create('class_room_revenue_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->foreignId('revenue_category_id')->constrained('revenue_categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_room_id', 'revenue_category_id'], 'cr_rc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_room_revenue_category');
    }
};
