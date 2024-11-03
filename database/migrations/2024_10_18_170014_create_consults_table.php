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
            $table->string('consult_title');
            $table->text('description');
            $table->string('consult_document_path')->nullable();
            $table->double('consult_document_size')->default(0);
            $table->foreignId('dosbing_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');
            $table->string('reply_title')->nullable();
            $table->text('reply')->nullable();
            $table->string('reply_document_path')->nullable();
            $table->double('reply_document_size')->default(0);
            $table->dateTime('reply_at')->nullable();
            $table->integer('rating')->nullable();
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
