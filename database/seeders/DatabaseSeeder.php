<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\PeriodoFiscal;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Crear Roles
        Rol::firstOrCreate(['rol_id' => 1], ['rol_nombre' => 'Administrador']);
        Rol::firstOrCreate(['rol_id' => 2], ['rol_nombre' => 'Operador']);

        // 2. Crear Usuario Administrador por defecto
        Usuario::firstOrCreate(
            ['usu_correo' => 'admin@admin.com'],
            [
                'usu_dni' => '12345678',
                'usu_nombres' => 'Admin',
                'usu_apellidos' => 'Sistema',
                'password' => Hash::make('password'),
                'rol_id' => 1,
                'usu_activo' => 1
            ]
        );

        // 3. Crear Periodo Fiscal Inicial
        PeriodoFiscal::firstOrCreate(
            ['per_anio' => 2026],
            ['per_cerrado' => 0]
        );
    }
}