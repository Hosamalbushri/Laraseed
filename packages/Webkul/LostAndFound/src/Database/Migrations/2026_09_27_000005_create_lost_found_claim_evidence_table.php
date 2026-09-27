<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_found_claim_evidence', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('claim_id');
            $table->string('evidence_type', 32);
            $table->text('text_value')->nullable();
            $table->text('file_path')->nullable();
            $table->text('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('byte_size')->nullable();
            $table->dateTime('submitted_at');
            $table->timestamps();

            $table->foreign('claim_id')
                ->references('id')->on('lost_found_claims')
                ->restrictOnDelete();
            $table->index(['claim_id', 'evidence_type'], 'lf_claim_evidence_claim_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_found_claim_evidence');
    }
};
