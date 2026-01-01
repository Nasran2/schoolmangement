<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Basic admission details
            $table->string('first_name')->nullable()->after('name');
            $table->string('other_names')->nullable()->after('first_name');
            $table->string('name_with_initial')->nullable()->after('other_names');
            $table->string('gender', 20)->nullable()->after('phone');
            $table->date('date_of_birth')->nullable()->after('gender');

            // Parent/guardian
            $table->string('parent_address')->nullable()->after('address');
            $table->string('religion')->nullable()->after('parent_address');
            $table->string('desired_class')->nullable()->after('religion');

            // Medical
            $table->text('medical_history')->nullable()->after('desired_class');
            $table->boolean('long_term_medication')->default(false)->after('medical_history');
            $table->boolean('learning_disabilities')->default(false)->after('long_term_medication');

            // Previous school
            $table->string('previous_school')->nullable()->after('learning_disabilities');
            $table->string('previous_grade')->nullable()->after('previous_school');

            // Siblings
            $table->text('siblings')->nullable()->after('previous_grade');
            $table->boolean('has_siblings_in_college')->default(false)->after('siblings');

            // Father details
            $table->string('father_name_with_initial')->nullable()->after('has_siblings_in_college');
            $table->string('father_nic_passport')->nullable()->after('father_name_with_initial');
            $table->string('father_religion')->nullable()->after('father_nic_passport');
            $table->string('father_nationality')->nullable()->after('father_religion');
            $table->string('father_occupation')->nullable()->after('father_nationality');
            $table->string('father_phone')->nullable()->after('father_occupation');
            $table->string('father_whatsapp')->nullable()->after('father_phone');
            $table->string('father_office_phone')->nullable()->after('father_whatsapp');
            $table->string('father_emergency_number')->nullable()->after('father_office_phone');

            // Mother details
            $table->string('mother_name_with_initial')->nullable()->after('father_emergency_number');
            $table->string('mother_nic_passport')->nullable()->after('mother_name_with_initial');
            $table->string('mother_religion')->nullable()->after('mother_nic_passport');
            $table->string('mother_nationality')->nullable()->after('mother_religion');
            $table->string('mother_occupation')->nullable()->after('mother_nationality');
            $table->string('mother_phone')->nullable()->after('mother_occupation');
            $table->string('mother_whatsapp')->nullable()->after('mother_phone');
            $table->string('mother_office_phone')->nullable()->after('mother_whatsapp');
            $table->string('mother_emergency_number')->nullable()->after('mother_office_phone');

            // Photo + agreement
            $table->string('passport_photo_path')->nullable()->after('mother_emergency_number');
            $table->boolean('admission_agree')->default(false)->after('passport_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'other_names',
                'name_with_initial',
                'gender',
                'date_of_birth',
                'parent_address',
                'religion',
                'desired_class',
                'medical_history',
                'long_term_medication',
                'learning_disabilities',
                'previous_school',
                'previous_grade',
                'siblings',
                'has_siblings_in_college',
                'father_name_with_initial',
                'father_nic_passport',
                'father_religion',
                'father_nationality',
                'father_occupation',
                'father_phone',
                'father_whatsapp',
                'father_office_phone',
                'father_emergency_number',
                'mother_name_with_initial',
                'mother_nic_passport',
                'mother_religion',
                'mother_nationality',
                'mother_occupation',
                'mother_phone',
                'mother_whatsapp',
                'mother_office_phone',
                'mother_emergency_number',
                'passport_photo_path',
                'admission_agree',
            ]);
        });
    }
};
