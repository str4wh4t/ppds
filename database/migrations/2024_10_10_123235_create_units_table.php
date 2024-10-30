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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            // create field name, user_id constraint from users table
            $table->string('name');
            $table->foreignId('kaprodi_user_id')->nullable()
                ->constrained('users')
                ->onDelete('set null') // Mengubah dari 'cascade' menjadi 'set null'
                ->comment('user_id dari user yang memiliki role kaprodi');
            $table->string('schedule_document_path')->nullable();
            $table->string('guideline_document_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
