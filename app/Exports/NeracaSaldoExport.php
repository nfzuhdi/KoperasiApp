<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NeracaSaldoExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithCustomStartCell
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        $collection = collect();
        
        // Add data rows
        foreach ($this->data['neraca_saldo'] as $item) {
            $collection->push([
                $item->kode_akun,
                $item->nama_akun,
                $item->posisi_normal,
                $item->saldo_debet > 0 ? number_format($item->saldo_debet, 0, ',', '.') : '',
                $item->saldo_kredit > 0 ? number_format($item->saldo_kredit, 0, ',', '.') : '',
            ]);
        }
        
        // Add total row
        $collection->push([
            '',
            'TOTAL',
            '',
            number_format($this->data['total_debet'], 0, ',', '.'),
            number_format($this->data['total_kredit'], 0, ',', '.'),
        ]);
        
        return $collection;
    }

    public function headings(): array
    {
        return [
            ['NERACA SALDO'],
            ['Periode: ' . $this->data['periode']],
            ['Dicetak: ' . $this->data['tanggal_cetak']],
            [''],
            ['Kode Akun', 'Nama Akun', 'Posisi Normal', 'Debet', 'Kredit']
        ];
    }

    public function startCell(): string
    {
        return 'A1';
    }

    public function title(): string
    {
        return 'Neraca Saldo';
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(20);
        
        // Title styling
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        // Period and date styling
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        // Header styling
        $sheet->getStyle('A5:E5')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFE0E0E0',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        // Data styling
        $dataStartRow = 6;
        $dataEndRow = $lastRow - 1;
        
        if ($dataEndRow >= $dataStartRow) {
            $sheet->getStyle("A{$dataStartRow}:E{$dataEndRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);
            
            // Right align numeric columns
            $sheet->getStyle("D{$dataStartRow}:E{$dataEndRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ]);
        }
        
        // Total row styling
        $sheet->getStyle("A{$lastRow}:E{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'FFFFCC00',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ],
        ]);
        
        // Center align total label
        $sheet->getStyle("B{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        return [];
    }
}
