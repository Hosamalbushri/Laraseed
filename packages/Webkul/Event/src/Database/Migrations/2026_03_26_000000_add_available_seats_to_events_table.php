<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'organizer')) {
                $table->unsignedInteger('available_seats')->nullable()->after('organizer');
            } else {
                $table->unsignedInteger('available_seats')->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('available_seats');
        });
    }
};
