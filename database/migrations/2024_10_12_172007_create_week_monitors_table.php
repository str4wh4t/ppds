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
        Schema::create('week_monitors', function (Blueprint $table) {
            $table->id();
            // constraint to table users
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->year('year');
            $table->tinyInteger('week');
            $table->integer('week_group_id')->index();
            $table->bigInteger('workload_as_seconds');
            $table->string('workload');
            $table->bigInteger('workload_hours');
            $table->bigInteger('workload_hours_not_allowed');
            $table->timestamps();
            $table->unique(['user_id', 'week_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('week_monitors');
    }
};
