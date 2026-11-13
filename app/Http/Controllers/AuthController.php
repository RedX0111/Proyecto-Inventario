<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;

class AuthController extends Controller
{
    /**
     * Muestra la vista del formulario de login.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Procesa las credenciales del usuario y gestiona la redirección por rol.
     */
    public function login(Request $request)
    {
        $request->validate([
            'usu_dni' => 'required|string',
            'password' => 'required|string',
        ]);

        // 1. Buscar el usuario por DNI
        $user = Usuario::where('usu_dni', $request->usu_dni)->first();

        // 2. Validar si existe
        if (!$user) {
            return back()->withErrors(['usu_dni' => 'El DNI ingresado no existe en la base de datos.'])->onlyInput('usu_dni');
        }

        // 3. Validar si se encuentra activo
        if ($user->usu_activo != 1) {
            return back()->withErrors(['usu_dni' => 'El usuario se encuentra inactivo.'])->onlyInput('usu_dni');
        }

        // 4. Validar la contraseña cifrada (CORREGIDO: Ahora usa ->password)
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['usu_dni' => 'La contraseña es incorrecta.'])->onlyInput('usu_dni');
        }

        // 5. Iniciar sesión manualmente
        Auth::login($user);
        $request->session()->regenerate();

        // 6. Redirección inteligente basada en el ID del rol (rol_id)
        if ($user->rol_id == 1) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->rol_id == 2) {
            return redirect()->route('auditoria.limpieza');
        } else {
            return redirect()->route('auditoria.padron');
        }
    }

    /**
     * Cierra la sesión activa del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}