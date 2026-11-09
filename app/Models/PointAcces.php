<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PointAcces extends Model {
    protected $table = 'points_acces';
    protected $guarded = ['id'];
    protected $casts = ['actif' => 'boolean', 'capacites' => 'array', 'derniere_vue_at' => 'datetime'];
    public function mesures() { return $this->hasMany(MesureRssi::class); }
    public function batiment() { return $this->belongsTo(Batiment::class); }
    /** Normalise un BSSID / Called-Station-Id : "AA-BB-CC-DD-EE-FF:UPL" -> "aabbccddeeff" */
    public static function normalize(?string $raw): ?string {
        if (!$raw || !preg_match('/(?:[0-9a-f]{2}[:\-.]?){5}[0-9a-f]{2}/i', $raw, $m)) return null;
        return strtolower(preg_replace('/[^0-9a-f]/i', '', $m[0]));
    }
}
