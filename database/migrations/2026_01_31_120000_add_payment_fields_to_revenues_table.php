<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            if (! Schema::hasColumn('revenues', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('amount');
            }
            if (! Schema::hasColumn('revenues', 'payment_status')) {
                // confirmed|pending|rejected
                $table->string('payment_status')->default('confirmed')->after('payment_method');
            }
            if (! Schema::hasColumn('revenues', 'payment_meta')) {
                $table->json('payment_meta')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('revenues', 'cheque_date')) {
                $table->date('cheque_date')->nullable()->after('payment_meta');
            }
            if (! Schema::hasColumn('revenues', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('cheque_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            if (Schema::hasColumn('revenues', 'confirmed_at')) {
                $table->dropColumn('confirmed_at');
            }
            if (Schema::hasColumn('revenues', 'cheque_date')) {
                $table->dropColumn('cheque_date');
            }
            if (Schema::hasColumn('revenues', 'payment_meta')) {
                $table->dropColumn('payment_meta');
            }
            if (Schema::hasColumn('revenues', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('revenues', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
