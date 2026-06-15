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
        Schema::table('extra_classes', function (Blueprint $table) {
            $table->text('class_room_ids')->nullable()->after('class_room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('extra_classes', function (Blueprint $table) {
            $table->dropColumn('class_room_ids');
        });
    }
};
