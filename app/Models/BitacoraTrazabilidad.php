<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraTrazabilidad extends Model
{
    protected $table = 'aud_bitacora_trazabilidad';
    protected $primaryKey = 'aud_id';
    public $timestamps = false; // Solo maneja created_at por defecto según tu DDL
    protected $guarded = [];

    public function activo()
    {
        return $this->belongsTo(ActivoMaestro::class, 'act_id', 'act_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usu_id', 'usu_id');
    }
}