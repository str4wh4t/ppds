<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('checkout_latitude', 10, 7)->nullable()->after('checkout_photo_path');
            $table->decimal('checkout_longitude', 10, 7)->nullable()->after('checkout_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['checkout_latitude', 'checkout_longitude']);
        });
    }
};
