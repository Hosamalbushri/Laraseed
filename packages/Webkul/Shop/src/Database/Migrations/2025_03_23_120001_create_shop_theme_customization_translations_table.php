<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_theme_customization_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_theme_customization_id');
            $table->string('locale');
            $table->json('options');

            $table->unique(['shop_theme_customization_id', 'locale'], 'stc_trans_locale_uq');

            $table->foreign('shop_theme_customization_id', 'stc_trans_cust_fk')
                ->references('id')
                ->on('shop_theme_customizations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_theme_customization_translations');
    }
};
