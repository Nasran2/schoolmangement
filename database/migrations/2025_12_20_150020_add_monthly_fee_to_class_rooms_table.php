<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->decimal('monthly_fee', 12, 2)->default(0)->after('active');
            $table->foreignId('monthly_fee_revenue_category_id')
                ->nullable()
                ->after('monthly_fee')
                ->constrained('revenue_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropForeign(['monthly_fee_revenue_category_id']);
            $table->dropColumn(['monthly_fee', 'monthly_fee_revenue_category_id']);
        });
    }
};
