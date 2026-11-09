<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    protected $table = 'mar_marcas';
    protected $primaryKey = 'mar_id';
    public $timestamps = false;
    protected $guarded = [];

    public function modelos()
    {
        return $this->hasMany(Modelo::class, 'mar_id', 'mar_id');
    }
}