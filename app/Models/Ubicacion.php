<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    protected $table = 'bd_ubicaciones';
    protected $primaryKey = 'ubi_id';
    public $timestamps = false;
    protected $guarded = [];

    public function censosCampo()
    {
        return $this->hasMany(CensoCampo::class, 'ubi_id', 'ubi_id');
    }
}