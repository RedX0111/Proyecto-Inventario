<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{
    protected $table = 'mod_modelos';
    protected $primaryKey = 'mod_id';
    public $timestamps = false;
    protected $guarded = [];

    public function marca()
    {
        return $this->belongsTo(Marca::class, 'mar_id', 'mar_id');
    }

    public function activos()
    {
        return $this->hasMany(ActivoMaestro::class, 'mod_id', 'mod_id');
    }
}