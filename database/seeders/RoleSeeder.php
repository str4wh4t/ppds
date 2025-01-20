<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            'system',
            'admin_prodi',
            'admin_fakultas',
            'kaprodi',
            'dosen',
            'dekan',
            'komkordik',
            'student',
            'wd1',
            'dirsdm',
            'kadep',
        ];

        foreach ($roles as $role) {
            // Cek apakah role sudah ada, jika belum buat baru
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
