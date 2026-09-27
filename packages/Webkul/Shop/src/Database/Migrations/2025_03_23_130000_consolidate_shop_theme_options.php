<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shop_theme_customizations')) {
            return;
        }

        Schema::table('shop_theme_customizations', function (Blueprint $table) {
            if (! Schema::hasColumn('shop_theme_customizations', 'options')) {
                $table->json('options')->nullable()->after('theme_code');
            }
        });

        if (Schema::hasTable('shop_theme_customization_translations')) {
            $rows = DB::table('shop_theme_customization_translations')
                ->orderBy('id')
                ->get()
                ->groupBy('shop_theme_customization_id');

            foreach ($rows as $customizationId => $group) {
                $options = [];
                foreach ($group as $row) {
                    $decoded = json_decode($row->options, true);
                    if (is_array($decoded) && $decoded !== []) {
                        $options = $decoded;

                        break;
                    }
                    $options = is_array($decoded) ? $decoded : [];
                }

                DB::table('shop_theme_customizations')
                    ->where('id', $customizationId)
                    ->update(['options' => json_encode($options)]);
            }

            Schema::dropIfExists('shop_theme_customization_translations');
        }

        DB::table('shop_theme_customizations')
            ->whereNull('options')
            ->update(['options' => json_encode([])]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('shop_theme_customizations')) {
            return;
        }

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

        $customizations = DB::table('shop_theme_customizations')->get();

        foreach ($customizations as $row) {
            $opts = $row->options ?? null;
            if ($opts === null || $opts === '') {
                continue;
            }
            DB::table('shop_theme_customization_translations')->insert([
                'shop_theme_customization_id' => $row->id,
                'locale' => config('app.locale', 'en'),
                'options' => is_string($opts) ? $opts : json_encode($opts),
            ]);
        }

        Schema::table('shop_theme_customizations', function (Blueprint $table) {
            if (Schema::hasColumn('shop_theme_customizations', 'options')) {
                $table->dropColumn('options');
            }
        });
    }
};
