<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_custody_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('found_item_id');
            $table->string('event_type', 32);
            $table->unsignedInteger('actor_user_id');
            $table->unsignedInteger('from_custodian_user_id')->nullable();
            $table->unsignedInteger('to_custodian_user_id');
            $table->text('from_storage_location')->nullable();
            $table->text('to_storage_location');
            $table->text('notes')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->foreign('found_item_id')
                ->references('id')->on('lost_found_items')
                ->restrictOnDelete();
            $table->foreign('actor_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
            $table->foreign('from_custodian_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();
            $table->foreign('to_custodian_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->index(
                ['found_item_id', 'occurred_at', 'id'],
                'lf_custody_item_occurred_index',
            );
            $table->index('actor_user_id', 'lf_custody_actor_index');
            $table->index('to_custodian_user_id', 'lf_custody_to_custodian_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_custody_records');
    }
};
