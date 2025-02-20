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
        Schema::table('week_monitors', function (Blueprint $table) {
            $table->integer('month')->default(0)->after('year');
            $table->integer('week_month')->default(0)->after('week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('week_monitors', function (Blueprint $table) {
            $table->dropColumn(['month_index', 'week_month']);
        });
    }
};