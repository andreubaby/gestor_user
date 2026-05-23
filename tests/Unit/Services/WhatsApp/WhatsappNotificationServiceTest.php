<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Models\User;
use App\Services\OpenWA\OpenWAClient;
use App\Services\WhatsApp\WhatsappNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappNotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // En tests, apuntamos mysql_trabajadores a sqlite para poder validar tfno.
        config()->set('database.connections.mysql_trabajadores', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('database.connections.mysql_polifonia', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::purge('mysql');
        DB::purge('mysql_trabajadores');
        DB::purge('mysql_polifonia');

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('password');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('usuarios_vinculados')) {
            Schema::create('usuarios_vinculados', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable();
                $table->unsignedBigInteger('usuario_id')->nullable();
                $table->unsignedBigInteger('trabajador_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::connection('mysql_trabajadores')->hasTable('users')) {
            Schema::connection('mysql_trabajadores')->create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->string('tfno')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::connection('mysql_polifonia')->hasTable('trabajadores')) {
            Schema::connection('mysql_polifonia')->create('trabajadores', function (Blueprint $table) {
                $table->id();
                $table->string('tfno')->nullable();
            });
        }
    }

    public function test_send_to_user_uses_tfno_from_mysql_trabajadores_users_table(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test-tfno-' . Str::uuid() . '@example.com',
            'password' => bcrypt('secret'),
            'phone' => null,
        ]);

        DB::connection('mysql_trabajadores')->table('users')->insert([
            'id' => 9991,
            'name' => 'Trabajador remoto',
            'email' => 'trabajador@example.com',
            'password' => bcrypt('secret'),
            'tfno' => '600111222',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('usuarios_vinculados')->insert([
            'uuid' => (string) Str::uuid(),
            'usuario_id' => $user->id,
            'trabajador_id' => 9991,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var OpenWAClient&\Mockery\MockInterface $client */
        $client = $this->mock(OpenWAClient::class, function ($mock) {
            $mock->shouldReceive('sendText')
                ->once()
                ->with('600111222', 'hola tfno')
                ->andReturn(['ok' => true]);
        });

        $service = new WhatsappNotificationService($client);
        $service->sendToUser($user, 'hola tfno', false);

        $this->assertTrue(true);
    }

    public function test_send_to_user_falls_back_to_mysql_polifonia_trabajadores_table(): void
    {
        $user = User::create([
            'name' => 'Fallback User',
            'email' => 'test-fallback-' . Str::uuid() . '@example.com',
            'password' => bcrypt('secret'),
            'phone' => null,
        ]);

        DB::connection('mysql_polifonia')->table('trabajadores')->insert([
            'id' => 9992,
            'tfno' => '600333444',
        ]);

        DB::table('usuarios_vinculados')->insert([
            'uuid' => (string) Str::uuid(),
            'usuario_id' => $user->id,
            'trabajador_id' => 9992,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        /** @var OpenWAClient&\Mockery\MockInterface $client */
        $client = $this->mock(OpenWAClient::class, function ($mock) {
            $mock->shouldReceive('sendText')
                ->once()
                ->with('600333444', 'hola fallback')
                ->andReturn(['ok' => true]);
        });

        $service = new WhatsappNotificationService($client);
        $service->sendToUser($user, 'hola fallback', false);

        $this->assertTrue(true);
    }
}






