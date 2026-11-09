<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StagingCenso extends Model
{
    protected $table = 'bd_staging_censo';
    protected $primaryKey = 'stg_id';
    public $timestamps = false;
    protected $guarded = [];
}