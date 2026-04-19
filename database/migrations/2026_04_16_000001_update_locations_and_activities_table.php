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
        Schema::table('locations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('description');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('created_via', 20)->default('web')->after('is_allowed');
            $table->json('device_info')->nullable()->after('created_via');
            $table->boolean('is_overdue_checkout')->default(false)->after('device_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'created_via', 'device_info', 'is_overdue_checkout']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
