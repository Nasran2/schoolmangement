<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guards also permit the documented phpMyAdmin installation on hosts without a terminal.
        foreach (['destination', 'deleted_at', 'deleted_by', 'deletion_reason', 'version'] as $column) {
            if (! Schema::hasColumn('owner_transfers', $column)) {
                Schema::table('owner_transfers', function (Blueprint $table) use ($column) {
                    match ($column) {
                        'destination' => $table->string('destination', 190)->nullable(),
                        'deleted_at' => $table->timestamp('deleted_at')->nullable(),
                        'deleted_by' => $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete(),
                        'deletion_reason' => $table->text('deletion_reason')->nullable(),
                        'version' => $table->unsignedInteger('version')->default(0),
                    };
                });
            }
        }
        if (! Schema::hasTable('owner_transfer_returns')) {
            Schema::create('owner_transfer_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_transfer_id')->constrained('owner_transfers')->restrictOnDelete();
                $table->uuid('submission_id')->unique();
                $table->date('returned_at')->index();
                $table->decimal('amount', 12, 2);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_transfer_returns');
        Schema::table('owner_transfers', function (Blueprint $table) {
            $table->dropForeign(['deleted_by']);
            $table->dropColumn(['destination', 'deleted_at', 'deleted_by', 'deletion_reason', 'version']);
        });
    }
};
