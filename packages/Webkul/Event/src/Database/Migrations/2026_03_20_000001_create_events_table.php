<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id')->unsigned()->nullable();
            $table->string('title');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('event_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
