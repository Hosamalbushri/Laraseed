<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_claims', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('found_item_id');
            $table->unsignedBigInteger('claimant_student_id');
            $table->string('status', 32)->default('submitted');
            $table->dateTime('submitted_at');
            $table->dateTime('withdrawn_at')->nullable();
            $table->timestamps();

            $table->foreign('found_item_id')
                ->references('id')->on('lost_found_items')
                ->restrictOnDelete();
            $table->foreign('claimant_student_id')
                ->references('id')->on('students')
                ->restrictOnDelete();

            $table->unique(['found_item_id', 'claimant_student_id'], 'lf_claims_item_claimant_unique');
            $table->index(['found_item_id', 'status'], 'lf_claims_item_status_index');
            $table->index(['claimant_student_id', 'status'], 'lf_claims_claimant_status_index');
            $table->index(['status', 'submitted_at'], 'lf_claims_status_submitted_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_claims');
    }
};
