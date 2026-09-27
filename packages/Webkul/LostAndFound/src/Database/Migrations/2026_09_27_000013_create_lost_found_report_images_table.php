<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lost_found_report_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lost_report_id')
                ->constrained('lost_found_reports')
                ->onDelete('restrict');
            $table->string('storage_key')->unique();
            $table->string('mime_type');
            $table->unsignedInteger('byte_size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['lost_report_id', 'sort_order', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lost_found_report_images');
    }
};
