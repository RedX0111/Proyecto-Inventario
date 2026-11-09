<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'bd_usuarios';
    protected $primaryKey = 'usu_id';

    protected $fillable = [
        'usu_dni', 'usu_nombres', 'usu_apellidos', 'usu_correo', 'password', 'rol_id', 'usu_activo'
    ];

    protected $hidden = [
        'password',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'rol_id');
    }

    public function bitacoras()
    {
        return $this->hasMany(BitacoraTrazabilidad::class, 'usu_id', 'usu_id');
    }
}