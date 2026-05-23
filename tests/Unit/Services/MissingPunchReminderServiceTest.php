<?php

namespace Tests\Unit\Services;

use App\Services\FichajesDiariosService;
use App\Services\MissingPunchReminderService;
use App\Services\WhatsApp\WhatsappNotificationService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MissingPunchReminderServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        config()->set('database.connections.mysql_polifonia', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('database.connections.mysql_fichajes', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('database.connections.mysql_trabajadores', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::purge('mysql_polifonia');
        DB::purge('mysql_fichajes');
        DB::purge('mysql_trabajadores');

        if (!Schema::hasTable('usuarios_vinculados')) {
            Schema::create('usuarios_vinculados', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('trabajador_id')->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->unsignedBigInteger('user_fichaje_id')->nullable();
            });
        }

        if (!Schema::connection('mysql_polifonia')->hasTable('trabajadores')) {
            Schema::connection('mysql_polifonia')->create('trabajadores', function (Blueprint $table) {
                $table->id();
                $table->string('nombre')->nullable();
                $table->string('email')->nullable();
                $table->string('tfno')->nullable();
                $table->unsignedTinyInteger('activo')->default(1);
            });
        }

        if (!Schema::connection('mysql_polifonia')->hasTable('trabajadores_dias')) {
            Schema::connection('mysql_polifonia')->create('trabajadores_dias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('trabajador_id');
                $table->date('fecha');
                $table->string('tipo', 2);
            });
        }

        if (!Schema::connection('mysql_fichajes')->hasTable('punches')) {
            Schema::connection('mysql_fichajes')->create('punches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('type')->nullable();
                $table->integer('mood')->nullable();
                $table->dateTime('happened_at');
                $table->unsignedTinyInteger('is_manual')->nullable();
                $table->text('note')->nullable();
            });
        }

        if (!Schema::connection('mysql_fichajes')->hasTable('users')) {
            Schema::connection('mysql_fichajes')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email')->nullable();
                $table->string('work_mode')->nullable();
            });
        }

        if (!Schema::connection('mysql_trabajadores')->hasTable('fichar')) {
            Schema::connection('mysql_trabajadores')->create('fichar', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->dateTime('fecha_hora');
                $table->string('tipo')->nullable();
                $table->integer('bienestar')->nullable();
            });
        }

        if (!Schema::connection('mysql_trabajadores')->hasTable('users')) {
            Schema::connection('mysql_trabajadores')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email')->nullable();
                $table->string('tfno')->nullable();
            });
        }
    }

    public function test_preview_includes_workers_without_user_fichaje_id_and_counts_legacy_punches(): void
    {
        config()->set('fichajes.missing_punch.omit_emails', ['omitido@example.com']);

        DB::connection('mysql_polifonia')->table('trabajadores')->insert([
            ['id' => 1, 'nombre' => 'Sin Vinculo', 'email' => 'sin-vinculo@example.com', 'tfno' => '', 'activo' => 1],
            ['id' => 2, 'nombre' => 'Con Punch Nuevo', 'email' => 'con-punch@example.com', 'tfno' => '600111222', 'activo' => 1],
            ['id' => 3, 'nombre' => 'Con Punch Viejo', 'email' => 'con-viejo@example.com', 'tfno' => '600333444', 'activo' => 1],
            ['id' => 4, 'nombre' => 'Omitido', 'email' => 'omitido@example.com', 'tfno' => '600555666', 'activo' => 1],
            ['id' => 5, 'nombre' => 'Campaign', 'email' => 'campaign@example.com', 'tfno' => '600777888', 'activo' => 1],
        ]);

        DB::table('usuarios_vinculados')->insert([
            [
                'trabajador_id' => 2,
                'usuario_id' => 10,
                'user_fichaje_id' => 2002,
            ],
            [
                'trabajador_id' => 4,
                'usuario_id' => 11,
                'user_fichaje_id' => 2004,
            ],
            [
                'trabajador_id' => 5,
                'usuario_id' => 12,
                'user_fichaje_id' => 2005,
            ],
        ]);

        DB::connection('mysql_fichajes')->table('punches')->insert([
            'user_id' => 2002,
            'type' => 'in',
            'happened_at' => '2026-05-12 08:00:00',
        ]);

        DB::connection('mysql_fichajes')->table('users')->insert([
            ['id' => 2002, 'email' => 'con-punch@example.com', 'work_mode' => 'office'],
            ['id' => 2004, 'email' => 'omitido@example.com', 'work_mode' => 'office'],
            ['id' => 2005, 'email' => 'campaign@example.com', 'work_mode' => 'campaign'],
        ]);

        DB::connection('mysql_trabajadores')->table('fichar')->insert([
            'user_id' => 3,
            'fecha_hora' => '2026-05-12 09:15:00',
            'tipo' => 'I',
        ]);

        DB::connection('mysql_trabajadores')->table('users')->insert([
            ['id' => 901, 'email' => 'sin-vinculo@example.com', 'tfno' => '699111222'],
            ['id' => 902, 'email' => 'omitido@example.com', 'tfno' => '699555666'],
        ]);

        /** @var WhatsappNotificationService&\Mockery\MockInterface $whatsapp */
        $whatsapp = $this->mock(WhatsappNotificationService::class);
        $service = new MissingPunchReminderService($whatsapp, app(FichajesDiariosService::class));

        $report = $service->previewForDate(Carbon::createFromFormat('Y-m-d', '2026-05-12'));

        $this->assertSame('ok', $report['status']);
        $this->assertSame(5, $report['total_linked']);
        $this->assertSame(2, $report['total_with_punch']);
        $this->assertSame(1, $report['total_candidates']);
        $this->assertSame(2, $report['total_omitted']);

        $candidate = $report['candidates'][0] ?? null;

        $this->assertNotNull($candidate);
        $this->assertSame(1, $candidate['trabajador_id']);
        $this->assertSame('699111222', $candidate['tfno']);
        $this->assertNull($candidate['user_fichaje_id']);
        $omittedEmails = collect($report['omitted_candidates'] ?? [])->pluck('email')->all();
        $this->assertContains('omitido@example.com', $omittedEmails);
        $this->assertContains('campaign@example.com', $omittedEmails);
    }
}







