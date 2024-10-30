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
        Schema::create('stases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('unit_stases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')
                ->constrained('units')
                ->onDelete('restrict');
            $table->foreignId('stase_id')
                ->constrained('stases')
                ->onDelete('restrict');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
        });

        Schema::create('unit_stase_users', function (Blueprint $table) { // UNTUK DOSEN
            $table->id();
            $table->foreignId('unit_stase_id')
                ->constrained('unit_stases')
                ->onDelete('restrict');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stases');
        Schema::dropIfExists('unit_stases');
        Schema::dropIfExists('unit_stase_users');
    }
};
