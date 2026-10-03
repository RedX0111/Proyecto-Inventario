<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRol
{
    public function handle(Request $request, Closure $next, ...$rolesPermitidos)
    {
        $usuario = $request->user();

        // 1. Obtener el nombre del rol desde la nueva tabla usando el rol_id del usuario
        $rolActual = \Illuminate\Support\Facades\DB::table('bd_roles')
                        ->where('rol_id', $usuario->rol_id)
                        ->value('rol_nombre');

        // Normalizamos a mayúsculas por seguridad (o 'DESCONOCIDO' si hubo error)
        $rolActual = $rolActual ? strtoupper($rolActual) : 'DESCONOCIDO';

        // 2. Si el rol del usuario no está en la lista de permitidos, lo bloqueamos
        if (!in_array($rolActual, $rolesPermitidos)) {
            abort(403, 'ACCESO DENEGADO. VIOLACIÓN DE SEGURIDAD: TU ROL DE ' . $rolActual . ' NO TIENE PERMISOS PARA ESTA ACCIÓN.');
        }

        return $next($request);
    }
}