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
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan field student_unit_id yang bisa null dan memiliki foreign key ke tabel units
            $table->smallInteger('semester')->nullable()->before('created_at');
            $table->foreignId('student_unit_id')
                ->nullable() // Membuat kolom student_unit_id boleh null
                ->constrained('units')
                ->onDelete('restrict') // Optional: menentukan aksi ketika unit dihapus
                ->before('created_at'); // Optional: menentukan aksi ketika unit dihapus
            $table->foreignId('dosbing_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->before('created_at');
            $table->foreignId('doswal_user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->before('created_at');
            $table->boolean('is_read_guideline')->default(false)->before('created_at');
            $table->timestamp('read_guideline_at')->nullable()->before('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key constraint dan kolom student_unit_id
            $table->dropForeign(['student_unit_id']);
            $table->dropColumn('student_unit_id');
            $table->dropForeign(['dosbing_user_id']);
            $table->dropColumn('dosbing_user_id');
        });
    }
};
