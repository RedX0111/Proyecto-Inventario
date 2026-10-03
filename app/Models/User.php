<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'bd_usuarios';
    protected $primaryKey = 'usu_id';
    public $timestamps = false; 

    protected $fillable = [
        'rol_id', 'usu_dni', 'usu_nombres', 'usu_apellidos', 'usu_correo', 'usu_password', 'usu_activo'
    ];

    protected $hidden = [
        'usu_password',
    ];

    // Decirle a Laravel qué columna maneja el password
    public function getAuthPasswordName()
    {
        return 'usu_password';
    }

    public function getRolNombreAttribute()
    {
        return $this->usu_rol ?? 'OPERADOR_CAMPO';
    }
}