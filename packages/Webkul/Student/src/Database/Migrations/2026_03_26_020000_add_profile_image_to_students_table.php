<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'profile_image')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('profile_image')->nullable()->after('academic_level');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'profile_image')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('profile_image');
            });
        }
    }
};
