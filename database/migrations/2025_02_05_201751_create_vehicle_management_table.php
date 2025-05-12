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
        Schema::create('vehicle_management', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_number_id')->constrained('lot_numbers')->onDelete('cascade');
            $table->string('license_plate')->nullable();
            $table->string('permit_id')->nullable();
            $table->enum('status', ['Tenant', 'Employee', 'Visitor', 'Do Not Tag', 'Other'])->default('Visitor');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('duration_type', ['1 Day', '7 Days', '1 Month', '1 Year', '5 Years']);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_management');
    }
};
