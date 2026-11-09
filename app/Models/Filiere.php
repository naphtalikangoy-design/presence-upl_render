<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Filiere extends Model {
    protected $fillable = ['faculte_id','nom'];
    public function faculte() { return $this->belongsTo(Faculte::class); }
}
