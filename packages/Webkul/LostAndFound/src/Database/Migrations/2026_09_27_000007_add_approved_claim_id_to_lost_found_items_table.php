<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lost_found_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('approved_claim_id')->nullable()->unique();
            $table->foreign('approved_claim_id')
                ->references('id')->on('lost_found_claims')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lost_found_items', function (Blueprint $table): void {
            $table->dropForeign(['approved_claim_id']);
            $table->dropUnique(['approved_claim_id']);
            $table->dropColumn('approved_claim_id');
        });
    }
};
