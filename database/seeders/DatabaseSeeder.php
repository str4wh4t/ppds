<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        \App\Models\User::factory()->create([
            'username' => 'Super',
            'fullname' => 'Super User',
            'identity' => '1234567890',
            'email' => 'super@fk.undip.ac.id',
            'password' => Hash::make(config('constants.password_default')),
        ]);

        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RoleHasPermissionsSeeder::class,
        ]);

        $user = \App\Models\User::first();
        $user->assignRole('system');
    }
}
