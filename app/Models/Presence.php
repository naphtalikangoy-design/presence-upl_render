<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Presence extends Model {
    protected $guarded = ['id'];
    protected $casts = ['date' => 'date', 'pointe_a' => 'datetime', 'wifi_verifie' => 'boolean', 'corrige' => 'boolean',
        'latitude' => 'float', 'longitude' => 'float', 'precision_gps' => 'float', 'distance_m' => 'float'];
    public function stagiaire() { return $this->belongsTo(Stagiaire::class); }
    public function batiment() { return $this->belongsTo(Batiment::class); }
    public function pointAcces() { return $this->belongsTo(PointAcces::class); }
    public function sessionReseau() { return $this->belongsTo(SessionReseau::class); }
}
