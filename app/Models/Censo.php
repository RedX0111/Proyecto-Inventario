<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Censo extends Model
{
    protected $table = 'cen_censos';
    protected $primaryKey = 'cer_id';
    public $timestamps = false;
    protected $guarded = [];

    public function periodoFiscal()
    {
        return $this->belongsTo(PeriodoFiscal::class, 'per_id', 'per_id');
    }

    public function censosCampo()
    {
        return $this->hasMany(CensoCampo::class, 'cer_id', 'cer_id');
    }
}