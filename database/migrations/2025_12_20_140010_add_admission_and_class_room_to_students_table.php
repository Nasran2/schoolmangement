<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('admission_number', 50)->nullable()->unique()->after('id');
            $table->foreignId('class_room_id')->nullable()->constrained('class_rooms')->nullOnDelete()->after('class');
            $table->unsignedInteger('promoted_until_year')->nullable()->after('class_room_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['class_room_id']);
            $table->dropColumn(['class_room_id', 'promoted_until_year']);
            $table->dropUnique(['admission_number']);
            $table->dropColumn('admission_number');
        });
    }
};
