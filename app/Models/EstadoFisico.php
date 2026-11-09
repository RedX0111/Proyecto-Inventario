<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoFisico extends Model
{
    protected $table = 'bd_estados_fisicos';
    protected $primaryKey = 'est_id';
    public $timestamps = false;
    protected $guarded = [];

    public function censosCampo()
    {
        return $this->hasMany(CensoCampo::class, 'est_id', 'est_id');
    }
}