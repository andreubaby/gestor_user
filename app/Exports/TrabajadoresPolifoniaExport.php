<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class TrabajadoresPolifoniaExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private $rows) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Email',
            'Vinculado',
            'Activo',
            'Último fichaje',
        ];
    }

    public function map($r): array
    {
        $vinculado = !empty($r->uuid) ? 'Sí' : 'No';
        $activo    = ((int)($r->activo ?? 0) === 1) ? 'Sí' : 'No';

        $ultimoFichaje = '';
        if (!empty($r->ultimo_fichaje)) {
            $ultimoFichaje = Carbon::parse($r->ultimo_fichaje)
                ->format('d/m/Y H:i');
        }

        return [
            $r->nombre ?? '',
            $r->email ?? '',
            $vinculado,
            $activo,
            $ultimoFichaje,
        ];
    }
}
