<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = [
            'access-control',
            'reset-password',
            'mahasiswa.crud',
            'dosen.crud',
            'kaprodi.crud',
            'unit.crud',
            'stase.crud',
            'stase-location.crud',
            'logbook.crud',
            'portofolio.crud',
            'consult.crud',
            'speak.crud',
            'report-logbook',
            'get-notifs',
            'week-monitor.index'
        ];

        foreach ($permissions as $permissionName) {
            // Cek apakah permission sudah ada, jika belum buat baru
            Permission::firstOrCreate(['name' => $permissionName]);
            $role = Role::findByName('system');
            $role->givePermissionTo($permissionName);
        }
    }
}
