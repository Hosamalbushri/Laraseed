<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lost_found_items', function (Blueprint $table): void {
            $table->unsignedInteger('current_custodian_user_id')->nullable();
            $table->string('current_storage_location', 255)->nullable();
            $table->dateTime('custody_started_at')->nullable();
            $table->dateTime('custody_changed_at')->nullable();

            $table->foreign('current_custodian_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
            $table->index('current_custodian_user_id', 'lf_items_current_custodian_index');
        });
    }

    public function down(): void
    {
        Schema::table('lost_found_items', function (Blueprint $table): void {
            $table->dropForeign(['current_custodian_user_id']);
            $table->dropIndex('lf_items_current_custodian_index');
            $table->dropColumn([
                'current_custodian_user_id',
                'current_storage_location',
                'custody_started_at',
                'custody_changed_at',
            ]);
        });
    }
};
