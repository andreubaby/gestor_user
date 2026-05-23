<?php

namespace App\Console\Commands;

use App\Models\TrabajadorPolifonia;
use App\Models\User;
use App\Models\UserTrabajador;
use App\Models\UsuarioVinculado;
use Illuminate\Console\Command;

class OpenWADebugPhone extends Command
{
    protected $signature = 'openwa:debug-phone {userId : ID de users.id}';

    protected $description = 'Muestra como se resuelve el telefono para OpenWA desde mysql_trabajadores.users.tfno con fallback a mysql_polifonia.trabajadores.tfno';

    public function handle(): int
    {
        $userId = (int) $this->argument('userId');

        $user = User::query()->find($userId);
        if (!$user) {
            $this->error("Usuario no encontrado: {$userId}");
            return self::FAILURE;
        }

        $trabajadorId = UsuarioVinculado::query()
            ->where('usuario_id', $user->id)
            ->value('trabajador_id');

        if (!$trabajadorId) {
            $this->warn('No existe vinculo en usuarios_vinculados para este usuario.');
            $this->line('Resultado: sin telefono para OpenWA.');
            return self::SUCCESS;
        }

        $tfnoTrabajadoresUsers = UserTrabajador::query()
            ->whereKey($trabajadorId)
            ->value('tfno');

        $tfnoPolifonia = TrabajadorPolifonia::on('mysql_polifonia')
            ->whereKey($trabajadorId)
            ->value('tfno');

        $source = null;
        $tfno = null;

        if (!empty($tfnoTrabajadoresUsers)) {
            $tfno = $tfnoTrabajadoresUsers;
            $source = 'mysql_trabajadores.users.tfno';
        } elseif (!empty($tfnoPolifonia)) {
            $tfno = $tfnoPolifonia;
            $source = 'mysql_polifonia.trabajadores.tfno';
        }

        $phone = $this->normalizePhone((string) $tfno);
        $chatId = $phone !== '' ? $this->toChatId($phone) : null;

        $this->line('OpenWA debug phone');
        $this->line('-----------------');
        $this->line("user_id: {$user->id}");
        $this->line("trabajador_id: {$trabajadorId}");
        $this->line('tfno mysql_trabajadores.users: ' . ($tfnoTrabajadoresUsers ?? 'null'));
        $this->line('tfno mysql_polifonia.trabajadores: ' . ($tfnoPolifonia ?? 'null'));
        $this->line('source: ' . ($source ?? 'null'));
        $this->line('tfno (raw): ' . ($tfno ?? 'null'));
        $this->line('tfno (normalized): ' . ($phone !== '' ? $phone : 'null'));
        $this->line('chat_id: ' . ($chatId ?? 'null'));

        if ($chatId === null) {
            $this->warn('No se puede generar chat_id porque tfno esta vacio o invalido.');
        }

        return self::SUCCESS;
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) <= 9) {
            $digits = (string) config('openwa.default_country_code', '34') . $digits;
        }

        return $digits;
    }

    protected function toChatId(string $normalizedPhone): string
    {
        return $normalizedPhone . '@c.us';
    }
}

