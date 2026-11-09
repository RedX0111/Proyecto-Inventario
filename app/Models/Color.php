<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $table = 'col_colores';
    protected $primaryKey = 'col_id';
    public $timestamps = false;
    protected $guarded = [];

    public function activos()
    {
        return $this->hasMany(ActivoMaestro::class, 'col_id', 'col_id');
    }
}