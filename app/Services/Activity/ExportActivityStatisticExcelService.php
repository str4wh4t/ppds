<?php

namespace App\Services\Activity;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportActivityStatisticExcelService
{
    /**
     * @param  iterable<int, array<string, mixed>|\Illuminate\Database\Eloquent\Model>  $tableData
     * @param  iterable<int, array<string, mixed>|\Illuminate\Database\Eloquent\Model>  $weekMonitorStats
     * @param  array{year: mixed, month: mixed, week: mixed}  $filters
     */
    public function download(iterable $tableData, iterable $weekMonitorStats, array $filters): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $this->fillParticipationSheet($spreadsheet, $tableData, $filters);
        $this->fillWorkloadSheet($spreadsheet, $weekMonitorStats, $filters);

        $filename = $this->buildFilename($filters);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  iterable<int, array<string, mixed>|\Illuminate\Database\Eloquent\Model>  $tableData
     * @param  array{year: mixed, month: mixed, week: mixed}  $filters
     */
    private function fillParticipationSheet(Spreadsheet $spreadsheet, iterable $tableData, array $filters): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pengisian');

        $sheet->setCellValue('A1', 'Statistik Pengisian Week Monitor');
        $sheet->setCellValue('A2', $this->filterLabel($filters));
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = ['Prodi', 'Jml Mhs', 'Mengisi', 'Belum', 'Jml Mhs (%)'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 4], $header);
        }
        $this->styleHeaderRow($sheet, 4, 5);

        $row = 5;
        $totalUsers = 0;
        $totalMonitored = 0;
        $totalNotMonitored = 0;

        foreach ($tableData as $unit) {
            $unit = (object) $unit;
            $totalUsers += (int) $unit->total_users;
            $totalMonitored += (int) $unit->monitored_users;
            $totalNotMonitored += (int) $unit->not_monitored_users;

            $sheet->setCellValue("A{$row}", $unit->name);
            $sheet->setCellValue("B{$row}", (int) $unit->total_users);
            $sheet->setCellValue("C{$row}", (int) $unit->monitored_users);
            $sheet->setCellValue("D{$row}", (int) $unit->not_monitored_users);
            $sheet->setCellValue("E{$row}", (float) $unit->percentage);
            $row++;
        }

        $averagePercentage = $totalUsers > 0
            ? round(($totalMonitored / $totalUsers) * 100, 2)
            : 0;

        $sheet->setCellValue("A{$row}", 'Total');
        $sheet->setCellValue("B{$row}", $totalUsers);
        $sheet->setCellValue("C{$row}", $totalMonitored);
        $sheet->setCellValue("D{$row}", $totalNotMonitored);
        $sheet->setCellValue("E{$row}", $averagePercentage);
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);

        $this->autosizeColumns($sheet, range('A', 'E'));
        $this->centerNumericColumns($sheet, ['B', 'C', 'D', 'E'], 5, $row);
    }

    /**
     * @param  iterable<int, array<string, mixed>|\Illuminate\Database\Eloquent\Model>  $weekMonitorStats
     * @param  array{year: mixed, month: mixed, week: mixed}  $filters
     */
    private function fillWorkloadSheet(Spreadsheet $spreadsheet, iterable $weekMonitorStats, array $filters): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Beban Kerja');

        $sheet->setCellValue('A1', 'Statistik Beban Kerja');
        $sheet->setCellValue('A2', $this->filterLabel($filters));
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = ['Prodi', 'Kurang 71 Jam', '71 - 80 Jam', 'Lebih 80 Jam', 'Total Data'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 4], $header);
        }
        $this->styleHeaderRow($sheet, 4, 5);

        $row = 5;
        foreach ($weekMonitorStats as $unit) {
            $unit = (object) $unit;
            $sheet->setCellValue("A{$row}", $unit->name);
            $sheet->setCellValue("B{$row}", (int) $unit->workload_below_71);
            $sheet->setCellValue("C{$row}", (int) $unit->workload_71_to_80);
            $sheet->setCellValue("D{$row}", (int) $unit->workload_above_80);
            $sheet->setCellValue("E{$row}", (int) $unit->total_monitored_users);
            $row++;
        }

        $lastDataRow = max(5, $row - 1);
        $this->autosizeColumns($sheet, range('A', 'E'));
        $this->centerNumericColumns($sheet, ['B', 'C', 'D', 'E'], 5, $lastDataRow);
    }

    /**
     * @param  array{year: mixed, month: mixed, week: mixed}  $filters
     */
    private function filterLabel(array $filters): string
    {
        $parts = [];

        if (! empty($filters['year'])) {
            $parts[] = 'Tahun: '.$filters['year'];
        }
        if (! empty($filters['month'])) {
            $parts[] = 'Bulan: '.$filters['month'];
        }
        if (! empty($filters['week'])) {
            $parts[] = 'Minggu: '.$filters['week'];
        }

        return $parts === [] ? 'Semua periode' : implode(' | ', $parts);
    }

    /**
     * @param  array{year: mixed, month: mixed, week: mixed}  $filters
     */
    private function buildFilename(array $filters): string
    {
        $parts = ['statistic'];

        if (! empty($filters['year'])) {
            $parts[] = (string) $filters['year'];
        }
        if (! empty($filters['month'])) {
            $parts[] = 'bulan-'.$filters['month'];
        }
        if (! empty($filters['week'])) {
            $parts[] = 'minggu-'.$filters['week'];
        }

        return implode('-', $parts).'.xlsx';
    }

    private function styleHeaderRow($sheet, int $row, int $columnCount): void
    {
        $range = 'A'.$row.':'.chr(64 + $columnCount).$row;
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E5E7EB');
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function autosizeColumns($sheet, array $columns): void
    {
        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function centerNumericColumns($sheet, array $columns, int $startRow, int $endRow): void
    {
        if ($endRow < $startRow) {
            return;
        }

        foreach ($columns as $column) {
            $sheet->getStyle("{$column}{$startRow}:{$column}{$endRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
}
