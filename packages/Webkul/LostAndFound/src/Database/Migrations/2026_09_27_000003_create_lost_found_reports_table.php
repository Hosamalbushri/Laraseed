<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_reports', function (Blueprint $table) {
            $table->id();
            $table->string('public_reference', 64);
            $table->string('public_reference_key', 64)->unique();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('resolved_found_item_id')->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('title', 160);
            $table->text('public_description')->nullable();
            $table->text('private_description')->nullable();
            $table->string('lost_location', 255)->nullable();
            $table->dateTime('lost_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
            $table->foreign('category_id')->references('id')->on('lost_found_categories')->restrictOnDelete();
            $table->foreign('resolved_found_item_id')->references('id')->on('lost_found_items')->restrictOnDelete();

            $table->index(['student_id', 'status']);
            $table->index(['status', 'category_id', 'lost_at']);
            $table->index('resolved_found_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_reports');
    }
};
