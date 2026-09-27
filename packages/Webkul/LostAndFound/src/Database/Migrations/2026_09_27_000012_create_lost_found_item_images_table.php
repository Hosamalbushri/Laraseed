<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_item_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('found_item_id');
            $table->unsignedInteger('created_by_user_id');
            $table->string('visibility', 32);
            $table->string('storage_key', 255);
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('byte_size');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('found_item_id')->references('id')->on('lost_found_items')->restrictOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->restrictOnDelete();

            $table->unique('storage_key');
            $table->index(['found_item_id', 'visibility', 'sort_order', 'id']);
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_item_images');
    }
};
