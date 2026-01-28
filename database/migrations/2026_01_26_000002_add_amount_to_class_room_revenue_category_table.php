<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_room_revenue_category', function (Blueprint $table) {
            if (!Schema::hasColumn('class_room_revenue_category', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->after('revenue_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('class_room_revenue_category', function (Blueprint $table) {
            if (Schema::hasColumn('class_room_revenue_category', 'amount')) {
                $table->dropColumn('amount');
            }
        });
    }
};
