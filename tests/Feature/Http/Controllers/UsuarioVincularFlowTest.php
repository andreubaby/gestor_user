<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Usuario;
use App\Models\UsuarioVinculado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class UsuarioVincularFlowTest extends TestCase
{
    public function test_it_returns_linking_suggestion_for_email(): void
    {
        $this->requireDatabaseOrSkip();
        $this->withoutMiddleware();

        $usuario = Usuario::create([
            'nombre' => 'Usuario Sugerido',
            'email' => 'sugerido@example.com',
            'password' => 'secret',
        ]);

        UsuarioVinculado::create([
            'uuid' => (string) Str::uuid(),
            'usuario_id' => $usuario->id,
        ]);

        $response = $this->getJson(route('usuarios.vincular.suggestions', [
            'email' => 'sugerido@example.com',
        ]));

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'suggestion' => [
                    'email' => 'sugerido@example.com',
                    'ids' => [
                        'usuario_id' => $usuario->id,
                    ],
                ],
            ]);
    }

    public function test_it_supports_save_and_next_flow(): void
    {
        $this->requireDatabaseOrSkip();
        $this->withoutMiddleware();

        $first = Usuario::create([
            'nombre' => 'Primero',
            'email' => 'a-next@example.com',
            'password' => 'secret',
        ]);

        $next = Usuario::create([
            'nombre' => 'Segundo',
            'email' => 'b-next@example.com',
            'password' => 'secret',
        ]);

        $uuid = (string) Str::uuid();

        $response = $this->post(route('usuarios.vincular.store'), [
            'uuid' => $uuid,
            'usuario_id' => $first->id,
            'email_search' => $first->email,
            'continue_workflow' => '1',
        ]);

        $response->assertRedirect(route('usuarios.vincular', ['email' => $next->email]));

        $this->assertDatabaseHas('usuarios_vinculados', [
            'uuid' => $uuid,
            'usuario_id' => $first->id,
        ]);
    }

    private function requireDatabaseOrSkip(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB principal no disponible en este entorno: ' . $e->getMessage());
        }
    }
}


