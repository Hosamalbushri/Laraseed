<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_locale_settings', function (Blueprint $table): void {
            // The only permitted key makes this a single-row authority.
            $table->enum('key', ['primary'])->primary();
            $table->foreignId('primary_locale_id')->constrained('locales')->restrictOnDelete();
            $table->timestamps();
        });

        // Content primary is deliberately independent of APP_LOCALE and Admin core_config.
        $english = DB::table('locales')->where('code', 'en')->first();

        if (! $english) {
            throw new LogicException('The foundational English locale must exist before content-primary bootstrap.');
        }

        DB::table('locales')->where('id', $english->id)->update(['is_active' => true]);

        DB::table('content_locale_settings')->insert([
            'key' => 'primary',
            'primary_locale_id' => $english->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('content_locale_settings');
    }
};
