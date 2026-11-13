<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        DB::table('bd_roles')->insertOrIgnore([
            ['rol_id' => 1, 'rol_nombre' => 'Administrador'],
            ['rol_id' => 2, 'rol_nombre' => 'Operador'],
        ]);

        DB::table('bd_usuarios')->insertOrIgnore([
            [
                'usu_id' => 1,
                'usu_dni' => '12345678',
                'usu_nombres' => 'Admin',
                'usu_apellidos' => 'Sistema',
                'usu_correo' => 'admin@admin.com',
                'password' => Hash::make('admin123'),
                'rol_id' => 1,
                'usu_activo' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        DB::table('bd_periodos_fiscales')->insertOrIgnore([
            ['per_id' => 1, 'per_anio' => 2026, 'per_cerrado' => 0]
        ]);
    }
}