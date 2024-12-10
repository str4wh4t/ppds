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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->foreignId('unit_id')
                ->constrained('units')
                ->onDelete('restrict');
            $table->integer('month_number');
            $table->string('month_name');
            $table->integer('year');
            $table->string('document_path')->nullable();
            // tambahkan unique constraint untuk unit_id, month_number, year
            $table->unique(['unit_id', 'month_number', 'year']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
