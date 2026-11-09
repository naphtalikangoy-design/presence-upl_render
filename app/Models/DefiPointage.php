<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DefiPointage extends Model {
    protected $table = 'defis_pointage';
    protected $guarded = ['id'];
    protected $casts = ['details' => 'array', 'est_demo' => 'boolean', 'mesure_at' => 'datetime', 'expire_at' => 'datetime', 'utilise_at' => 'datetime', 'autorise' => 'boolean', 'gps_verifie' => 'boolean'];
}
