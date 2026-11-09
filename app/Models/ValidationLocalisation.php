<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ValidationLocalisation extends Model {
    protected $table = 'validations_localisation';
    protected $guarded = ['id'];
    protected $casts = ['details' => 'array', 'est_demo' => 'boolean', 'mesure_at' => 'datetime', 'expire_at' => 'datetime', 'utilise_at' => 'datetime', 'autorise' => 'boolean', 'gps_verifie' => 'boolean'];
    public function user() { return $this->belongsTo(User::class); }
    public function batiment() { return $this->belongsTo(Batiment::class); }
}
