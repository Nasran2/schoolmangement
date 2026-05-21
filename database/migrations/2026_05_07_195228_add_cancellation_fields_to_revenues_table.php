<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            if (! Schema::hasColumn('revenues', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('confirmed_at');
            }
            if (! Schema::hasColumn('revenues', 'cancelled_by')) {
                $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('revenues', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('revenues', function (Blueprint $table) {
            if (Schema::hasColumn('revenues', 'cancelled_by')) {
                $table->dropConstrainedForeignId('cancelled_by');
            }
            if (Schema::hasColumn('revenues', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }
            if (Schema::hasColumn('revenues', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};
