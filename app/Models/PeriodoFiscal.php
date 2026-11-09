<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoFiscal extends Model
{
    protected $table = 'bd_periodos_fiscales';
    protected $primaryKey = 'per_id';
    protected $guarded = [];

    public function censos()
    {
        return $this->hasMany(Censo::class, 'per_id', 'per_id');
    }
}