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
        Schema::create('speaks', function (Blueprint $table) {
            $table->id();
            // buat kolom untuk user_id, description, consult_at, dosbing_user_id, is_read, reply, reply_at
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('restrict');
            $table->string('speak_title');
            $table->text('description');
            $table->string('speak_document_path')->nullable();
            $table->double('speak_document_size')->default(0);
            $table->foreignId('employee_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');
            $table->string('answer_title')->nullable();
            $table->text('answer')->nullable();
            $table->string('answer_document_path')->nullable();
            $table->double('answer_document_size')->default(0);
            $table->dateTime('answer_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('speaks');
    }
};
