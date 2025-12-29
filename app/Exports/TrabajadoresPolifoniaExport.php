<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TrabajadoresPolifoniaExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private $rows) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Nombre', 'Email', 'Vinculado', 'Activo'];
    }

    public function map($r): array
    {
        $vinculado = !empty($r->uuid) ? 'Sí' : 'No';
        $activo    = ((int)($r->activo ?? 0) === 1) ? 'Sí' : 'No';

        return [
            $r->nombre ?? '',
            $r->email ?? '',
            $vinculado,
            $activo,
        ];
    }
}
