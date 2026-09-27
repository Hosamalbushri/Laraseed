<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_item_private_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('found_item_id')->unique();
            $table->text('identifying_details')->nullable();
            $table->text('serial_fragment')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamps();

            $table->foreign('found_item_id')->references('id')->on('lost_found_items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_item_private_details');
    }
};
