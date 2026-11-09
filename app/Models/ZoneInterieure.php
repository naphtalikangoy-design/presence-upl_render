<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ZoneInterieure extends Model {
    protected $table = 'zones_interieures';
    protected $guarded = ['id'];
    protected $casts = ['details' => 'array', 'est_demo' => 'boolean', 'mesure_at' => 'datetime', 'expire_at' => 'datetime', 'utilise_at' => 'datetime', 'autorise' => 'boolean', 'gps_verifie' => 'boolean'];
    public function batiment() { return $this->belongsTo(Batiment::class); }
}
