<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'event_date')) {
                $table->date('event_end_date')->nullable()->after('event_date');
            } else {
                $table->date('event_end_date')->nullable()->after('title');
            }

            if (Schema::hasColumn('events', 'available_seats')) {
                $table->boolean('availability_use_seats')->default(true)->after('available_seats');
            } else {
                $table->boolean('availability_use_seats')->default(true);
            }

            $table->boolean('availability_use_end_date')->default(false)->after('availability_use_seats');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'event_end_date',
                'availability_use_seats',
                'availability_use_end_date',
            ]);
        });
    }
};
