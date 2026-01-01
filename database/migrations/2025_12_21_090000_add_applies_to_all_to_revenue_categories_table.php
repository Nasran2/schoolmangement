<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revenue_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('revenue_categories', 'applies_to_all')) {
                $table->boolean('applies_to_all')->default(true)->after('payment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('revenue_categories', function (Blueprint $table) {
            if (Schema::hasColumn('revenue_categories', 'applies_to_all')) {
                $table->dropColumn('applies_to_all');
            }
        });
    }
};
