<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class UnitsSeeder extends Seeder
{
    public function run()
    {
        // Path file Excel
        $filePath = database_path('seeders/xls/data_master_aplikasi_logbook_ppds.xlsx');

        // Load file Excel
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

        // Pilih sheet berdasarkan nama
        $sheet = $spreadsheet->getSheetByName('units');

        // Jika menggunakan indeks sheet, misalnya sheet pertama (0)
        // $sheet = $spreadsheet->getSheet(0);

        // Ambil data sebagai array
        $data = $sheet->toArray(null, true, true, true);

        // Skip header
        array_shift($data);

        foreach ($data as $row) {
            $unit = Unit::create([
                'name' => str_replace(["'", "."], '', $row['B']),
                'kaprodi_user_id' => $row['C'],
            ]);

            $user = User::find($row['C']);
            $user->assignRole('kaprodi');

            $user = User::find($row['D']);
            $user->assignRole('admin_prodi');

            $syncData = array_fill_keys([$row['D']], ['role_as' => 'admin_prodi']);
            $unit->unitAdmins()->sync($syncData);
        }

        $this->command->info('Units table seeded successfully!');
    }
}
