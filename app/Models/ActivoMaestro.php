<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivoMaestro extends Model
{
    protected $table = 'bd_activos_maestro';
    protected $primaryKey = 'act_id';
    protected $guarded = [];

    public function modelo()
    {
        return $this->belongsTo(Modelo::class, 'mod_id', 'mod_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'col_id', 'col_id');
    }

    public function clasificador()
    {
        return $this->belongsTo(Clasificador::class, 'cla_id', 'cla_id');
    }

    public function censoCampo()
    {
        return $this->hasOne(CensoCampo::class, 'act_id', 'act_id');
    }

    public function bitacoras()
    {
        return $this->hasMany(BitacoraTrazabilidad::class, 'act_id', 'act_id');
    }
}