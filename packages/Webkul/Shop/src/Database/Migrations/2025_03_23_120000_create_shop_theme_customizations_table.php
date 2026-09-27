<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_theme_customizations', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name');
            $table->integer('sort_order');
            $table->boolean('status')->default(false);
            $table->string('theme_code')->default('default');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_theme_customizations');
    }
};
