<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clasificador extends Model
{
    protected $table = 'sla_clasificadores';
    protected $primaryKey = 'cla_id';
    public $timestamps = false;
    protected $guarded = [];

    public function activos()
    {
        return $this->hasMany(ActivoMaestro::class, 'cla_id', 'cla_id');
    }
}