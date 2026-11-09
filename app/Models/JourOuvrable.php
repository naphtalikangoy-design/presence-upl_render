<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JourOuvrable extends Model { protected $table = 'jours_ouvrables'; protected $fillable = ['jour_semaine','actif'];
    protected $casts = ['actif' => 'boolean']; }
