<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locales', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 120);
            $table->enum('direction', ['ltr', 'rtl']);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order', 'code']);
        });

        // Fixed snapshot of config/app.php available_locales at foundation creation.
        // Reading mutable configuration here would make migration history non-deterministic.
        $timestamp = now();

        DB::table('locales')->insert([
            [
                'code' => 'ar',
                'name' => 'Arabic',
                'direction' => 'rtl',
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'direction' => 'ltr',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
