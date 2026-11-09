<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MesureRssi extends Model {
    protected $table = 'mesures_rssi';
    protected $guarded = ['id'];
    protected $casts = ['details' => 'array', 'est_demo' => 'boolean', 'mesure_at' => 'datetime', 'expire_at' => 'datetime', 'utilise_at' => 'datetime', 'autorise' => 'boolean', 'gps_verifie' => 'boolean'];
    public function pointAcces() { return $this->belongsTo(PointAcces::class); }
    public function session() { return $this->belongsTo(SessionReseau::class, 'session_reseau_id'); }
}
