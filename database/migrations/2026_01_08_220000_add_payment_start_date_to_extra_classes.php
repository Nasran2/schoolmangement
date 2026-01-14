<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('extra_classes', function (Blueprint $table) {
            $table->date('payment_start_date')->nullable()->after('fee');
        });
    }

    public function down(): void
    {
        Schema::table('extra_classes', function (Blueprint $table) {
            $table->dropColumn('payment_start_date');
        });
    }
};
