<?php

namespace App\Http\Controllers;

use App\Models\TrabajadorPolifonia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class RrhhDocumentosController extends Controller
{
    public function index()
    {
        $workers = TrabajadorPolifonia::query()
            ->select(['id','nombre','email','activo','nif','empresa'])
            ->orderBy('nombre')
            ->get();

        $docs = config('rrhh_docs.docs', []);

        $tipos = [];
        foreach ($docs as $key => $d) {
            $tipos[$key] = $d['label'] ?? $key;
        }

        return view('rrhh.documentos', [
            'workers' => $workers,
            'tipos'   => $tipos,
        ]);
    }

    // ------------------------------------------------------------
    // Paths / filenames
    // ------------------------------------------------------------

    private function resolveTemplatePath(?string $templateRelRaw): array
    {
        if (!$templateRelRaw) return [null, null];

        $templatesDir = trim((string) config('rrhh_docs.templates_dir', 'rrhh_templates'));
        $templatesDir = trim($templatesDir, "/\\");

        $rel = str_replace('\\', '/', $templateRelRaw);
        $rel = ltrim($rel, '/');

        if (!Str::startsWith($rel, $templatesDir . '/')) {
            $rel = $templatesDir . '/' . $rel;
        }

        $abs = storage_path('app/' . $rel);

        return [$rel, $abs];
    }

    private function buildFilename(array $doc, TrabajadorPolifonia $t, string $tipo, string $fecha): string
    {
        $filename = $doc['filename'] ?? 'documento_{tipo}_{nombre}_{fecha}.pdf';

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $fechaFmt = date('Y-m-d', strtotime($fecha));

        return str_replace(
            ['{tipo}','{nombre}','{dni}','{fecha}'],
            [$tipo, $safeNombre, (string) ($t->nif ?? ''), $fechaFmt],
            $filename
        );
    }

    // ------------------------------------------------------------
    // PDF builders routing
    // ------------------------------------------------------------

    private function buildPdfForTipo(string $tipo, TrabajadorPolifonia $t, string $puesto, string $fecha, string $templateAbs): ?string
    {
        return match ($tipo) {
            'epis_fumigador_entrega'            => $this->buildEpisFumigadorPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'maq_facilitador_pedidos_aut'       => $this->buildMaqFacilitadorPedidosPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'epis_general_entrega'              => $this->buildEpisGeneralPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'maq_produccion_aut'                => $this->buildMaqProduccionPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'epis_bandejero_entrega'            => $this->buildEpisBandejeroPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'maq_semillero_aut'                 => $this->buildMaqSemilleroPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'epis_soldador_entrega'             => $this->buildEpisSoldadorPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'maq_conductor_aut'                 => $this->buildMaqCondcutorPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'maq_siembra_aut'                   => $this->buildMaqSiembraPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'maq_bandejero_aut'                 => $this->buildMaqBandejeroPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'maq_empaquetadora_injertadora_aut' => $this->buildMaqEmpaquetadoraPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'maq_jefe_azul_aut'                 => $this->buildMaqJefeAzulPdf($t, $fecha, $puesto, $templateAbs, $tipo),
            'entrega_info'                      => $this->buildEntregaInfo($t, $fecha, $puesto, $templateAbs, $tipo),
            'vehiculo_uso_conservacion_aut'     => $this->buildVehiculoUso($t, $fecha, $puesto, $templateAbs, $tipo),
            'it2_manejo_segadora'               => $this->buildManejoSeg($t, $fecha, $puesto, $templateAbs, $tipo),
            default => null,
        };
    }

    // ------------------------------------------------------------
    // Actions
    // ------------------------------------------------------------

    public function pdf(Request $request): Response
    {
        $data = $request->validate([
            'trabajador_id' => ['required','integer','exists:mysql_polifonia.trabajadores,id'],
            'tipo'          => ['required','string'],
            'fecha'         => ['required','date'],
            'puesto'        => ['required','string','in:Oficina,Bandejero,Injertos,Camionero,Producción,Siembras'],
        ]);

        $t = TrabajadorPolifonia::query()->findOrFail((int) $data['trabajador_id']);

        $docs = config('rrhh_docs.docs', []);
        if (!isset($docs[$data['tipo']])) {
            abort(422, 'Tipo de documento no válido');
        }

        $doc = $docs[$data['tipo']];

        $templateRelRaw = $doc['template'] ?? null;
        [$templateRel, $templateAbs] = $this->resolveTemplatePath($templateRelRaw);

        if (!$templateRel || !$templateAbs) {
            abort(500, 'Plantilla no configurada para este documento');
        }

        Log::info('[RRHH DOCS] generar', [
            'trabajador_id'      => (int) $t->id,
            'nombre'             => $t->nombre,
            'nif'                => $t->nif ?? null,
            'empresa'            => $t->empresa ?? null,
            'tipo'               => $data['tipo'],
            'fecha'              => $data['fecha'],
            'template_rel_raw'   => $templateRelRaw,
            'template_rel_final' => $templateRel,
            'template_abs'       => $templateAbs,
        ]);

        if (!file_exists($templateAbs)) {
            Log::error('[RRHH DOCS] plantilla no existe', [
                'template_rel_final' => $templateRel,
                'template_abs'       => $templateAbs,
            ]);
            abort(404, "Plantilla no encontrada: {$templateRel}");
        }

        $filename = $this->buildFilename($doc, $t, $data['tipo'], $data['fecha']);

        // Intentar builder (si existe) y si pdftk está disponible
        try {
            $builtAbs = $this->buildPdfForTipo($data['tipo'], $t, $data['puesto'], $data['fecha'], $templateAbs);
            if ($builtAbs && file_exists($builtAbs)) {
                return response()->file($builtAbs, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$filename.'"',
                ]);
            }
        } catch (\Throwable $e) {
            // ✅ no rompemos la UX: si falla el relleno, devolvemos plantilla
            Log::error('[RRHH DOCS] fallo generando PDF relleno, devolviendo plantilla', [
                'tipo' => $data['tipo'],
                'error' => $e->getMessage(),
            ]);
        }

        return response()->file($templateAbs, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function zip(Request $request)
    {
        $data = $request->validate([
            'trabajador_id' => ['required','integer','exists:mysql_polifonia.trabajadores,id'],
            'fecha'         => ['required','date'],
            'puesto'        => ['required','string','in:Oficina,Bandejero,Injertos,Camionero,Producción,Siembras'],
            'tipos'         => ['required','array','min:1'],
            'tipos.*'       => ['required','string'],
        ]);

        $t = TrabajadorPolifonia::query()->findOrFail((int) $data['trabajador_id']);

        $docs = config('rrhh_docs.docs', []);

        $tipos = array_values(array_unique($data['tipos']));
        foreach ($tipos as $tipo) {
            if (!isset($docs[$tipo])) {
                abort(422, "Tipo de documento no válido: {$tipo}");
            }
        }

        $fecha = $data['fecha'];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $zipName = "RRHH_docs_{$safeNombre}_" . date('Y-m-d', strtotime($fecha)) . ".zip";
        $zipPath = storage_path('app/tmp/'.$zipName);

        $dir = dirname($zipPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            Log::error('[RRHH DOCS ZIP] No se pudo crear el directorio temporal', ['dir' => $dir]);
            abort(500, 'No se pudo crear el directorio temporal para el ZIP');
        }

        $zip = new ZipArchive();
        $open = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($open !== true) {
            abort(500, 'No se pudo crear el ZIP');
        }

        $added = 0;

        foreach ($tipos as $tipo) {
            $doc = $docs[$tipo];

            $templateRelRaw = $doc['template'] ?? null;
            [$templateRel, $templateAbs] = $this->resolveTemplatePath($templateRelRaw);

            if (!$templateRel || !$templateAbs || !file_exists($templateAbs)) {
                Log::warning('[RRHH DOCS ZIP] plantilla no encontrada', [
                    'tipo'               => $tipo,
                    'template_rel_raw'   => $templateRelRaw,
                    'template_rel_final' => $templateRel,
                    'template_abs'       => $templateAbs,
                ]);
                continue;
            }

            $filename = $this->buildFilename($doc, $t, $tipo, $fecha);

            // Intentar builder (si existe). Si falla, fallback a plantilla.
            try {
                $builtAbs = $this->buildPdfForTipo($tipo, $t, $data['puesto'], $fecha, $templateAbs);
                if ($builtAbs && file_exists($builtAbs)) {
                    $zip->addFile($builtAbs, $filename);
                    $added++;
                    continue;
                }
            } catch (\Throwable $e) {
                Log::warning('[RRHH DOCS ZIP] fallo generando PDF relleno, se mete plantilla', [
                    'tipo' => $tipo,
                    'error' => $e->getMessage(),
                ]);
            }

            $zip->addFile($templateAbs, $filename);
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            abort(404, 'No se pudo añadir ningún documento al ZIP (revisa plantillas y config)');
        }

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    // ------------------------------------------------------------
    // PDFTK helpers (AcroForm) with safe fallback
    // ------------------------------------------------------------

    private function hasPdftk(): bool
    {
        // 127 = command not found, así que comprobamos antes
        $cmd = 'command -v pdftk >/dev/null 2>&1';
        $code = null;
        @exec($cmd, $out, $code);
        return $code === 0;
    }

    private function makeFdf(array $fields): string
    {
        $escape = function ($v) {
            $v = (string) $v;
            $v = str_replace(['\\', '(', ')', "\r"], ['\\\\', '\(', '\)', ''], $v);
            return $v;
        };

        // Evitamos bytes raros en el header para no tener problemas de encoding
        $out = "%FDF-1.2\n%????\n1 0 obj\n<<\n/FDF << /Fields [\n";

        foreach ($fields as $name => $value) {
            $out .= "<< /T (" . $escape($name) . ") /V (" . $escape($value) . ") >>\n";
        }

        $out .= "] >>\n>>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
        return $out;
    }

    private function fillPdfWithPdftk(string $templateAbs, array $fields, string $outAbs): void
    {
        if (!$this->hasPdftk()) {
            Log::warning('[RRHH DOCS] pdftk no está instalado. Se omite relleno.', [
                'template' => $templateAbs,
            ]);
            throw new \RuntimeException('pdftk no instalado');
        }

        $dir = dirname($outAbs);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("No se pudo crear el directorio: {$dir}");
        }

        $fdf = $this->makeFdf($fields);
        $fdfPath = $dir . '/fdf_' . Str::random(10) . '.fdf';
        file_put_contents($fdfPath, $fdf);

        $cmd = [
            'pdftk',
            $templateAbs,
            'fill_form',
            $fdfPath,
            'output',
            $outAbs,
            'flatten',
        ];

        $descriptor = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = proc_open($cmd, $descriptor, $pipes);

        if (!is_resource($proc)) {
            @unlink($fdfPath);
            throw new \RuntimeException('No se pudo ejecutar pdftk (proc_open falló)');
        }

        $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);

        $code = proc_close($proc);
        @unlink($fdfPath);

        if ($code !== 0 || !file_exists($outAbs)) {
            Log::error('[RRHH DOCS] pdftk error', [
                'code'     => $code,
                'stderr'   => $stderr,
                'stdout'   => $stdout,
                'template' => $templateAbs,
                'out'      => $outAbs,
            ]);
            throw new \RuntimeException('Error rellenando PDF con pdftk');
        }
    }

    private function buildEpisFumigadorPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI en el mismo campo
        $nombreConDni = trim(
            (string)($t->nombre ?? '') .
            (!empty($t->nif) ? ' D.N.I. ' . (string)$t->nif : '')
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');

        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            // ❌ ya NO mandamos 'dni' como campo separado
            'nombreyapellidos' => $nombreConDni,
            'puesto'           => (string) $puesto,
            'trabajador'       => $nombreSolo, // o '' si prefieres
            'fecha'            => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' . $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildMaqFacilitadorPedidosPdf(
        TrabajadorPolifonia $t,
        string $fecha,
        string $puesto,
        string $templateAbs,
        string $tipo
    ): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y');

        $nombreConDni = trim(
            (string) ($t->nombre ?? '') .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');
        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            'nombreyapellidos'     => $nombreConDni,
            'puesto'               => (string)$puesto,
            'trabajador'           => $nombreSolo,
            'fecha'                => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string)($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' . $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildEpisGeneralPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        // 📅 Fecha bonita
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y');

        $nombreConDni = trim(
            (string)($t->nombre ?? '') .
            (!empty($t->nif) ? ' D.N.I. ' . (string)$t->nif : '')
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');
        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            // ❌ ya NO mandamos 'dni' como campo separado
            'nombreyapellidos' => $nombreConDni,
            'puesto'           => (string) $puesto,
            'trabajador'       => $nombreSolo, // o '' si prefieres
            'fecha'            => $fechaFmt,
        ];

        // 📂 Nombre de salida
        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        // 🧩 Rellenar PDF
        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildMaqProduccionPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos para que el DNI "se mueva" con el nombre
        $nombreConDni = trim(
            (string) ($t->nombre ?? '') .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');

        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            'nombreyapellidos'     => $nombreConDni,
            'puesto'               => (string)$puesto,
            'trabajador'           => $nombreSolo,
            'fecha'                => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildEpisBandejeroPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos (para evitar el corte y que "se mueva" el DNI)
        $nombreConDni = trim(
            (string)($t->nombre ?? '') .
            (!empty($t->nif) ? ' D.N.I. ' . (string)$t->nif : '')
        );
        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');
        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            // ❌ ya NO mandamos 'dni' como campo separado
            'nombreyapellidos' => $nombreConDni,
            'puesto'           => (string) $puesto,
            'trabajador'       => $nombreSolo, // o '' si prefieres
            'fecha'            => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildMaqSemilleroPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos para que el DNI "se mueva" con el nombre
        $nombreConDni = trim(
            (string) ($t->nombre ?? '') .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');

        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            'nombreyapellidos'     => $nombreConDni,
            'puesto'               => (string)$puesto,
            'trabajador'           => $nombreSolo,
            'fecha'                => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildEpisSoldadorPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos (para evitar el corte y que "se mueva" el DNI)
        $nombreConDni = trim(
            (string)($t->nombre ?? '') .
            (!empty($t->nif) ? ' D.N.I. ' . (string)$t->nif : '')
        );
        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');
        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            // ❌ ya NO mandamos 'dni' como campo separado
            'nombreyapellidos' => $nombreConDni,
            'puesto'           => (string) $puesto,
            'trabajador'       => $nombreSolo, // o '' si prefieres
            'fecha'            => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildMaqCondcutorPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos para que el DNI "se mueva" con el nombre
        $nombreConDni = trim(
            (string) ($t->nombre ?? '') .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');

        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            'nombreyapellidos'     => $nombreConDni,
            'puesto'               => (string)$puesto,
            'trabajador'           => $nombreSolo,
            'fecha'                => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildMaqSiembraPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos para que el DNI "se mueva" con el nombre
        $nombreConDni = trim(
            (string) ($t->nombre ?? '') .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');

        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            'nombreyapellidos'     => $nombreConDni,
            'puesto'               => (string)$puesto,
            'trabajador'           => $nombreSolo,
            'fecha'                => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildMaqBandejeroPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos para que el DNI "se mueva" con el nombre
        $nombreConDni = trim(
            (string) ($t->nombre ?? '') .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');

        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            'nombreyapellidos'     => $nombreConDni,
            'puesto'               => (string)$puesto,
            'trabajador'           => $nombreSolo,
            'fecha'                => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildMaqEmpaquetadoraPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos para que el DNI "se mueva" con el nombre
        $nombreConDni = trim(
            (string) ($t->nombre ?? '') .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');

        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            'nombreyapellidos'     => $nombreConDni,
            'puesto'               => (string)$puesto,
            'trabajador'           => $nombreSolo,
            'fecha'                => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildMaqJefeAzulPdf(TrabajadorPolifonia $t, string $fecha, string $puesto, string $templateAbs, string $tipo): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y'); // "16 de enero de 2026"

        // ✅ Acción 3: nombre + DNI juntos para que el DNI "se mueva" con el nombre
        $nombreConDni = trim(
            (string) ($t->nombre ?? '') .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        $nombreConDni = mb_convert_encoding($nombreConDni, 'ISO-8859-1', 'UTF-8');
        $puesto = mb_convert_encoding($puesto, 'ISO-8859-1', 'UTF-8');

        $nombreSolo = trim((string) ($t->nombre ?? ''));

        $fields = [
            'nombreyapellidos'     => $nombreConDni,
            'puesto'               => (string)$puesto,
            'trabajador'           => $nombreSolo,
            'fecha'                => $fechaFmt,
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) ($t->nombre ?? 'trabajador'));
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildEntregaInfo(
        TrabajadorPolifonia $t,
        string $fecha,
        string $puesto,
        string $templateAbs,
        string $tipo
    ): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y');

        $nombreSolo = trim(preg_replace('/\s+/', ' ', (string) ($t->nombre ?? '')));
        $dni        = (string) ($t->nif ?? '');
        $tfno       = (string) ($t->tfno ?? ''); // ajusta si el campo se llama movil/tlf/etc.

        // Helper encoding (evita "utilizaciÃ³n")
        $enc = fn ($s) => mb_convert_encoding((string) $s, 'ISO-8859-1', 'UTF-8');

        $fields = [
            'nombreyapellidos' => $enc($nombreSolo),
            'dni'              => $enc($dni),
            'tfno'             => $enc($tfno),
            'puesto'           => $enc($puesto),
            'fecha'            => $enc($fechaFmt),
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', $nombreSolo);
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildVehiculoUso(
        TrabajadorPolifonia $t,
        string $fecha,
        string $puesto,
        string $templateAbs,
        string $tipo
    ): string
    {
        // Nombre normalizado
        $nombreSolo = trim(preg_replace('/\s+/', ' ', (string) ($t->nombre ?? '')));
        // ✅ Acción 3: nombre + DNI juntos para que el DNI "se mueva" con el nombre
        $nombreConDni = trim(
            $nombreSolo .
            (!empty($t->nif) ? ' con D.N.I. ' . (string) $t->nif : '') .
            ' a la utilización'
        );

        // Encoding robusto (evita "utilizaciÃ³n" y similares)
        $toPdfEnc = function ($s) {
            $s = trim(preg_replace('/\s+/', ' ', (string) $s));
            // iconv suele ir fino con pdftk en muchos servidores
            $out = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s);
            return $out !== false ? $out : mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        };

        $fields = [
            'nombreyapellidos' => $toPdfEnc($nombreConDni),
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', $nombreSolo);
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }

    private function buildManejoSeg(
        TrabajadorPolifonia $t,
        string $fecha,
        string $puesto,
        string $templateAbs,
        string $tipo
    ): string
    {
        $fechaFmt = \Carbon\Carbon::parse($fecha)
            ->locale('es')
            ->translatedFormat('j \\d\\e F \\d\\e Y');

        $nombreSolo = trim(preg_replace('/\s+/', ' ', (string) ($t->nombre ?? '')));
        $dni        = (string) ($t->nif ?? '');
        $tfno       = (string) ($t->tfno ?? ''); // ajusta si el campo se llama movil/tlf/etc.

        // Helper encoding (evita "utilizaciÃ³n")
        $enc = fn ($s) => mb_convert_encoding((string) $s, 'ISO-8859-1', 'UTF-8');

        $fields = [
            'nombreyapellidos' => $enc($nombreSolo),
            'dni'              => $enc($dni),
            'tfno'             => $enc($tfno),
            'puesto'           => $enc($puesto),
            'fecha'            => $enc($fechaFmt),
        ];

        $safeNombre = preg_replace('/[^A-Za-z0-9 _\-]/', '', $nombreSolo);
        $safeNombre = trim(preg_replace('/\s+/', ' ', $safeNombre));
        $safeNombre = str_replace(' ', '_', $safeNombre);

        $outAbs = storage_path(
            'app/tmp/rrhh_pdf_' . $tipo . '_' .
            $safeNombre . '_' . date('Ymd_His') . '_' . Str::random(6) . '.pdf'
        );

        $this->fillPdfWithPdftk($templateAbs, $fields, $outAbs);

        return $outAbs;
    }
}
