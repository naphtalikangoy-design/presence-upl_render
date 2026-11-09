<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ZoneGeographique extends Model {
    protected $table = 'zones_geographiques';
    protected $fillable = ['batiment_id','nom','latitude','longitude','rayon_m','actif'];
    protected $casts = ['latitude' => 'float', 'longitude' => 'float', 'rayon_m' => 'integer', 'actif' => 'boolean'];
    public function batiment() { return $this->belongsTo(Batiment::class); }
}
