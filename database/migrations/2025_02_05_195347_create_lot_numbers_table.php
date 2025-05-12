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
        Schema::create('lot_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('lot_code')->unique();
            $table->string('address');
            $table->string('city');
            $table->integer('permits_per_month')->default(0);
            $table->string('duration')->nullable();
            $table->enum('status', ['Free', 'FreePaid'])->default('Free');
            $table->text('note')->nullable();
            $table->json('pricing')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_numbers');
    }
};
