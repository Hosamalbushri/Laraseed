<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lost_found_custody_records', function (Blueprint $table): void {
            $table->unsignedInteger('to_custodian_user_id')->nullable()->change();
            $table->text('to_storage_location')->nullable()->change();
        });

        Schema::create('lost_found_handovers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('found_item_id')->unique();
            $table->unsignedBigInteger('claim_id')->unique();
            $table->unsignedBigInteger('recipient_student_id');
            $table->unsignedInteger('staff_user_id');
            $table->text('verification_method');
            $table->text('verification_note')->nullable();
            $table->dateTime('handed_over_at');
            $table->timestamps();

            $table->foreign('found_item_id')
                ->references('id')->on('lost_found_items')
                ->restrictOnDelete();
            $table->foreign('claim_id')
                ->references('id')->on('lost_found_claims')
                ->restrictOnDelete();
            $table->foreign('recipient_student_id')
                ->references('id')->on('students')
                ->restrictOnDelete();
            $table->foreign('staff_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->index('recipient_student_id', 'lf_handovers_recipient_index');
            $table->index(['staff_user_id', 'handed_over_at'], 'lf_handovers_staff_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_handovers');

        DB::table('lost_found_custody_records')
            ->where('event_type', 'handed_over')
            ->delete();

        Schema::table('lost_found_custody_records', function (Blueprint $table): void {
            $table->unsignedInteger('to_custodian_user_id')->nullable(false)->change();
            $table->text('to_storage_location')->nullable(false)->change();
        });
    }
};
