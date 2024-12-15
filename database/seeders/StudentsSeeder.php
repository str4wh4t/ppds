<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path file Excel
        $filePath = database_path('seeders/xls/data_master_aplikasi_logbook_ppds.xlsx');

        // Load file Excel
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

        // Pilih sheet berdasarkan nama
        $sheet = $spreadsheet->getSheetByName('students');

        // Jika menggunakan indeks sheet, misalnya sheet pertama (0)
        // $sheet = $spreadsheet->getSheet(0);

        // Ambil data sebagai array
        $data = $sheet->toArray(null, true, true, true);

        // Skip header
        array_shift($data);

        foreach ($data as $row) {
            $user = User::create([
                'username' => str_replace(["'", "."], '', $row['B']), // Kolom username
                'identity' => str_replace(["'"], '', $row['C']), // Kolom identity
                'fullname' => $row['D'], // Kolom fullname
                'email' => $row['E'], // Kolom email
                'student_unit_id' => $row['F'],
                'dosbing_user_id' => empty($row['G']) ? null : $row['G'],
                'doswal_user_id' =>  empty($row['H']) ? null : $row['H'],
                'password' => Hash::make(config('constants.password_default')), // Kolom password
            ]);
            $user->assignRole('student');
        }

        $this->command->info('Students seeded successfully!');
    }
}
