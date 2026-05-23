<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class AttachmentUrlValidatorService
{
    /**
     * @return array<string, mixed>
     */
    public function validate(string $url, int $stepNumber): array
    {
        $maxBytes = (int) config('openwa.attachment_validation.max_size_bytes', 52428800);
        $allowedMimes = (array) config('openwa.attachment_validation.allowed_mimes', []);

        $response = Http::timeout((int) config('openwa.attachment_validation.timeout_seconds', 8))
            ->withHeaders(['User-Agent' => 'gestor-usuarios-attachment-validator'])
            ->head($url);

        if (!$response->successful()) {
            // Algunos servidores no soportan HEAD; intentamos GET liviano.
            $response = Http::timeout((int) config('openwa.attachment_validation.timeout_seconds', 8))
                ->withHeaders([
                    'User-Agent' => 'gestor-usuarios-attachment-validator',
                    'Range' => 'bytes=0-1024',
                ])
                ->get($url);
        }

        if (!$response->successful()) {
            throw new \InvalidArgumentException("El paso {$stepNumber} tiene un adjunto no accesible (HTTP {$response->status()}).");
        }

        $mime = strtolower(trim((string) $response->header('Content-Type', '')));
        if (str_contains($mime, ';')) {
            $mime = trim(explode(';', $mime)[0]);
        }

        $sizeHeader = $response->header('Content-Length');
        $size = is_numeric($sizeHeader) ? (int) $sizeHeader : null;

        if ($size !== null && $size > $maxBytes) {
            $mb = number_format($size / 1024 / 1024, 2);
            $maxMb = number_format($maxBytes / 1024 / 1024, 0);
            throw new \InvalidArgumentException("El paso {$stepNumber} tiene un adjunto demasiado grande ({$mb}MB > {$maxMb}MB).");
        }

        if ($mime !== '' && !empty($allowedMimes) && !in_array($mime, $allowedMimes, true)) {
            throw new \InvalidArgumentException("El paso {$stepNumber} tiene un adjunto con tipo no permitido ({$mime}).");
        }

        return [
            'url' => $url,
            'mime' => $mime,
            'size' => $size,
        ];
    }
}


