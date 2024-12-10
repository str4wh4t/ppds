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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            // add kolom user_id, name, start_date, end_date, time_spend, description, is_approved, approved_by, approved_at
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->string('name');
            // tambahkan kolom type
            $table->enum('type', config('constants.public.activity_types'))->nullable(); // untuk di kosongkan ketika digenerate system
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->time('time_spend');
            $table->text('description');
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');
            $table->dateTime('approved_at')->nullable();
            // tambahkan kolom user_stase_id dengan foreign key ke user_stases 
            $table->foreignId('unit_stase_id')
                ->nullable()
                ->constrained('unit_stases')
                ->onDelete('restrict');
            $table->integer('week_group_id');
            $table->foreign('week_group_id')
                ->references('week_group_id') // Kolom yang dirujuk di tabel `activities`
                ->on('week_monitors')            // Tabel yang dirujuk
                ->onDelete('restrict');
            $table->boolean('is_generated')->default(false);
            $table->boolean('is_allowed')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
