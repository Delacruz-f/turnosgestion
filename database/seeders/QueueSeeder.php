<?php

namespace Database\Seeders;

use App\Models\Counter;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QueueSeeder extends Seeder
{
    public function run(): void
    {
        $services = collect([
            ['name' => 'Trámite documentario', 'prefix' => 'TD', 'description' => 'Presentar o recoger documentos'],
            ['name' => 'Atención al ciudadano', 'prefix' => 'AC', 'description' => 'Consultas, reclamos y orientación'],
            ['name' => 'Pagos y tasas', 'prefix' => 'PG', 'description' => 'Caja: pago de tributos y derechos'],
        ])->map(fn ($s) => Service::firstOrCreate(['prefix' => $s['prefix']], $s));

        foreach ($services as $i => $service) {
            $user = User::firstOrCreate(
                ['email' => 'agente' . ($i + 1) . '@example.com'],
                ['name' => 'Agente ' . ($i + 1), 'password' => Hash::make('password')]
            );

            Counter::firstOrCreate(
                ['name' => 'Ventanilla ' . ($i + 1)],
                ['service_id' => $service->id, 'user_id' => $user->id]
            );
        }
    }
}
