<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Trabajador;
use Illuminate\Database\Seeder;

class AutomationTestSeeder extends Seeder
{
    public function run(): void
    {
        // Crear grupos locales de prueba
        Group::firstOrCreate(
            ['name' => 'Equipo de Ventas'],
            ['slug' => 'equipo-ventas', 'description' => 'Grupo de vendedores', 'active' => 1]
        );

        Group::firstOrCreate(
            ['name' => 'Directivos'],
            ['slug' => 'directivos', 'description' => 'Equipo directivo', 'active' => 1]
        );

        Group::firstOrCreate(
            ['name' => 'Todos'],
            ['slug' => 'todos', 'description' => 'Grupo general para todos', 'active' => 1]
        );

        $this->command->info('✅ Grupos de prueba creados para automatización');
    }
}


