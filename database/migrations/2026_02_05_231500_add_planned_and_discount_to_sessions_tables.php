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
        Schema::table('console_sessions', function (Blueprint $table) {
            $table->unsignedInteger('planned_duration_minutes')->nullable()->after('controller_count');
            $table->timestamp('planned_end_time')->nullable()->after('start_time');
            $table->unsignedTinyInteger('discount_percent')->nullable()->after('duration_minutes');
        });

        Schema::table('table_sessions', function (Blueprint $table) {
            $table->unsignedInteger('planned_duration_minutes')->nullable()->after('customer_id');
            $table->timestamp('planned_end_time')->nullable()->after('start_time');
            $table->unsignedTinyInteger('discount_percent')->nullable()->after('duration_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('console_sessions', function (Blueprint $table) {
            $table->dropColumn(['planned_duration_minutes', 'planned_end_time', 'discount_percent']);
        });

        Schema::table('table_sessions', function (Blueprint $table) {
            $table->dropColumn(['planned_duration_minutes', 'planned_end_time', 'discount_percent']);
        });
    }
};

