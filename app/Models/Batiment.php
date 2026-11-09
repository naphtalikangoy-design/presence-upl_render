<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Batiment extends Model {
    protected $fillable = ['nom','code','description','actif','pointage_actif'];
    protected $casts = ['actif' => 'boolean', 'pointage_actif' => 'boolean'];
    public function zonesInterieures() { return $this->hasMany(ZoneInterieure::class); }
    public function empreintes() { return $this->hasMany(EmpreinteWifi::class); }
    public function zones() { return $this->hasMany(ZoneGeographique::class); }
    public function pointsAcces() { return $this->hasMany(PointAcces::class); }
    public function presences() { return $this->hasMany(Presence::class); }
}
