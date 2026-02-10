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
        Schema::create('consoles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['PS4', 'PS5', 'XBOX', 'PC', 'Other'])->default('PS5');
            $table->enum('status', ['available', 'busy', 'maintenance'])->default('available');
            $table->decimal('hourly_rate_single', 10, 2);
            $table->decimal('hourly_rate_double', 10, 2);
            $table->decimal('hourly_rate_triple', 10, 2);
            $table->decimal('hourly_rate_quadruple', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consoles');
    }
};
