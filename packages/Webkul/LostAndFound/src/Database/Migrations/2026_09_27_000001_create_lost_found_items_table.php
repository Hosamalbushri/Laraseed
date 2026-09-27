<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_items', function (Blueprint $table) {
            $table->id();
            $table->string('public_reference', 64);
            $table->string('public_reference_key', 64)->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedInteger('logged_by_user_id');
            $table->string('status', 32)->default('draft');
            $table->string('title', 160);
            $table->text('public_description')->nullable();
            $table->string('found_location', 255)->nullable();
            $table->dateTime('found_at')->nullable();
            $table->dateTime('reported_at')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('lost_found_categories')->restrictOnDelete();
            $table->foreign('logged_by_user_id')->references('id')->on('users')->restrictOnDelete();

            $table->index(['status', 'category_id', 'found_at']);
            $table->index(['status', 'found_at']);
            $table->index('logged_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_items');
    }
};
