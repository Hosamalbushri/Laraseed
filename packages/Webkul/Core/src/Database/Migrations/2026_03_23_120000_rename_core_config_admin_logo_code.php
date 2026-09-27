<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align stored config codes with general.design.admin_logo (Bagisto-style keys).
     */
    public function up(): void
    {
        if (! Schema::hasTable('core_config')) {
            return;
        }

        DB::table('core_config')
            ->where('code', 'general.general.admin_logo.logo_image')
            ->update(['code' => 'general.design.admin_logo.logo_image']);
    }

    /**
     * Reverse the codes migration.
     */
    public function down(): void
    {
        if (! Schema::hasTable('core_config')) {
            return;
        }

        DB::table('core_config')
            ->where('code', 'general.design.admin_logo.logo_image')
            ->update(['code' => 'general.general.admin_logo.logo_image']);
    }
};
