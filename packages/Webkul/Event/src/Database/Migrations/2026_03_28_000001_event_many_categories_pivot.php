<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_event_category', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('event_id');
            $table->unsignedInteger('event_category_id');
            $table->timestamps();

            $table->unique(['event_id', 'event_category_id'], 'event_event_category_unique');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('event_category_id')->references('id')->on('event_categories')->onDelete('cascade');
        });

        if (Schema::hasColumn('events', 'category_id')) {
            $rows = DB::table('events')->whereNotNull('category_id')->get(['id', 'category_id']);
            foreach ($rows as $row) {
                DB::table('event_event_category')->insert([
                    'event_id' => $row->id,
                    'event_category_id' => $row->category_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('events', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('category_id')->nullable()->after('id');
            $table->foreign('category_id')->references('id')->on('event_categories')->onDelete('set null');
        });

        $pairs = DB::table('event_event_category')->orderBy('id')->get(['event_id', 'event_category_id']);
        foreach ($pairs as $p) {
            DB::table('events')->where('id', $p->event_id)->update(['category_id' => $p->event_category_id]);
        }

        Schema::dropIfExists('event_event_category');
    }
};
