<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lost_found_claim_evidence', function (Blueprint $table): void {
            $table->char('storage_key_hash', 64)
                ->nullable()
                ->unique()
                ->after('file_path');
        });
    }

    public function down(): void
    {
        Schema::table('lost_found_claim_evidence', function (Blueprint $table): void {
            $table->dropUnique(['storage_key_hash']);
            $table->dropColumn('storage_key_hash');
        });
    }
};
