<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmpreinteWifi extends Model {
    protected $table = 'empreintes_wifi';
    protected $guarded = ['id'];
    protected $casts = ['signature' => 'array', 'actif' => 'boolean', 'est_demo' => 'boolean', 'releve_at' => 'datetime'];
    public function batiment() { return $this->belongsTo(Batiment::class); }
    public function zone() { return $this->belongsTo(ZoneInterieure::class, 'zone_interieure_id'); }
    /** Classe effective : une empreinte "intérieur" dans une zone NON autorisée compte comme "exterieur". */
    public function classeEffective(): string {
        return ($this->classe === 'interieur' && (!$this->zone || $this->zone->autorise)) ? 'interieur' : 'exterieur';
    }
}
