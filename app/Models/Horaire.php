<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Horaire extends Model { protected $fillable = ['nom','fenetre_debut','heure_arrivee','heure_limite','heure_depart','fenetre_fin','actif'];
    protected $casts = ['actif' => 'boolean']; }
