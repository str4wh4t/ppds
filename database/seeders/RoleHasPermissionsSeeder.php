<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleHasPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        // Path file SQL
        $filePath = database_path('seeders/sql/role_has_permissions.sql');

        // Baca isi file SQL
        $sql = file_get_contents($filePath);

        // Jalankan perintah SQL
        DB::unprepared($sql);

        // $this->command->info('Role Has Permissions seeder has been run successfully!');
    }
}
