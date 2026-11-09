<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'bd_roles';
    protected $primaryKey = 'rol_id';
    public $timestamps = false;
    protected $guarded = [];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'rol_id', 'rol_id');
    }
}