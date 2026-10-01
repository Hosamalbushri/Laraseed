<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->default('person');
            $table->string('name', 255)->index();

            // Person-specific fields
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('job_title', 100)->nullable();

            // Organization-specific fields / Affiliation
            $table->string('organization_name', 255)->nullable();
            $table->string('tax_number', 50)->nullable()->index();

            // Communication & Web
            $table->string('email', 255)->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->string('mobile', 50)->nullable();
            $table->string('website', 255)->nullable();

            // Address
            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country_code', 2)->nullable()->index();

            // Metadata & Status
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            // Composite index for fast listing/filtering
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
