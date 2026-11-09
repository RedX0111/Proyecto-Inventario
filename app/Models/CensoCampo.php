<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensoCampo extends Model
{
    protected $table = 'bd_censo_campo';
    protected $primaryKey = 'cdc_id';
    public $timestamps = false;
    protected $guarded = [];

    public function censo()
    {
        return $this->belongsTo(Censo::class, 'cer_id', 'cer_id');
    }

    public function activo()
    {
        return $this->belongsTo(ActivoMaestro::class, 'act_id', 'act_id');
    }

    public function estadoFisico()
    {
        return $this->belongsTo(EstadoFisico::class, 'est_id', 'est_id');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubi_id', 'ubi_id');
    }
}