<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UsersSeeder extends Seeder
{
    public function run()
    {
        // Path file Excel
        $filePath = database_path('seeders/xls/data_master_aplikasi_logbook_ppds.xlsx');

        // Load file Excel
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

        // Pilih sheet berdasarkan nama
        $sheet = $spreadsheet->getSheetByName('users');

        // Jika menggunakan indeks sheet, misalnya sheet pertama (0)
        // $sheet = $spreadsheet->getSheet(0);

        // Ambil data sebagai array
        $data = $sheet->toArray(null, true, true, true);

        // Skip header
        array_shift($data);

        // Iterasi data dan insert ke tabel users
        foreach ($data as $row) {
            $user = User::create([
                'username' => str_replace(["'", "."], '', $row['B']), // Kolom username
                'identity' => str_replace(["'"], '', $row['C']), // Kolom identity
                'fullname' => $row['D'], // Kolom fullname
                'email' => $row['E'], // Kolom email
                'password' => Hash::make(config('constants.password_default')), // Kolom password
            ]);
            if ($row['F'] == 'dosen') {
                $user->assignRole('dosen');
            }
        }

        $this->command->info('Users table seeded successfully!');
    }
}
