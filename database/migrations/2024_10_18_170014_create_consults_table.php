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
        Schema::create('consults', function (Blueprint $table) {
            $table->id();
            // buat kolom untuk user_id, description, consult_at, dosbing_user_id, is_read, reply, reply_at
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->text('description');
            $table->json('files')->nullable();
            $table->dateTime('consult_at');
            $table->foreignId('dosbing_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');
            $table->boolean('is_read')->default(false);
            $table->text('reply')->nullable();
            $table->json('reply_files')->nullable();
            $table->dateTime('reply_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consults');
    }
};
