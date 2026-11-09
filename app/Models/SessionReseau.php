<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SessionReseau extends Model {
    protected $table = 'sessions_reseau';
    protected $guarded = ['id'];
    protected $casts = ['est_demo' => 'boolean', 'debut_at' => 'datetime', 'derniere_activite_at' => 'datetime', 'fin_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function pointAcces() { return $this->belongsTo(PointAcces::class); }
}
