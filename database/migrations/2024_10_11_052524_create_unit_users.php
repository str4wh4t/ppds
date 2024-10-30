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
        Schema::create('unit_users', function (Blueprint $table) {
            $table->id();

            // Field unit_id dengan foreign key ke tabel units
            $table->foreignId('unit_id')
                ->constrained('units')
                ->onDelete('cascade'); // Menghapus entri jika unit dihapus

            // Field user_id dengan foreign key ke tabel users
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade'); // Menghapus entri jika user dihapus

            // Field role_as sebagai enum
            $table->enum('role_as', ['admin_prodi', 'dosen']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_users');
    }
};
