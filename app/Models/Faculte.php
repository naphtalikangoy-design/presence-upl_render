<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Faculte extends Model {
    protected $fillable = ['nom','code'];
    public function filieres() { return $this->hasMany(Filiere::class); }
    public function stagiaires() { return $this->hasMany(Stagiaire::class); }
}
