<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_claim_reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('claim_id');
            $table->unsignedInteger('reviewer_user_id');
            $table->string('from_status', 32);
            $table->string('to_status', 32);
            $table->text('claimant_message')->nullable();
            $table->text('staff_notes')->nullable();
            $table->dateTime('reviewed_at');
            $table->timestamps();

            $table->foreign('claim_id')
                ->references('id')->on('lost_found_claims')
                ->restrictOnDelete();
            $table->foreign('reviewer_user_id')
                ->references('id')->on('users')
                ->restrictOnDelete();

            $table->index(['claim_id', 'reviewed_at', 'id'], 'lf_claim_reviews_claim_time_index');
            $table->index('reviewer_user_id', 'lf_claim_reviews_reviewer_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_claim_reviews');
    }
};
