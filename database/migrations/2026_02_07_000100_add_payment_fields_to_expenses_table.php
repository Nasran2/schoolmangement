<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'payment_method')) {
                $table->string('payment_method', 30)->default('cash')->after('amount');
            }
            if (! Schema::hasColumn('expenses', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('expenses', 'cheque_date')) {
                $table->date('cheque_date')->nullable()->after('payment_meta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'cheque_date')) {
                $table->dropColumn('cheque_date');
            }
            if (Schema::hasColumn('expenses', 'payment_meta')) {
                $table->dropColumn('payment_meta');
            }
            if (Schema::hasColumn('expenses', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
