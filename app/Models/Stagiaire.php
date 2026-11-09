<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Stagiaire extends Model
{
    protected $fillable = ['user_id','nom','postnom','prenom','sexe','faculte_id','filiere_id','promotion_id',
        'entreprise_id','telephone','photo','date_debut_stage','date_fin_stage'];
    protected $casts = ['date_debut_stage' => 'date', 'date_fin_stage' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function faculte(): BelongsTo { return $this->belongsTo(Faculte::class); }
    public function filiere(): BelongsTo { return $this->belongsTo(Filiere::class); }
    public function promotion(): BelongsTo { return $this->belongsTo(Promotion::class); }
    public function entreprise(): BelongsTo { return $this->belongsTo(EntrepriseAccueil::class, 'entreprise_id'); }
    public function presences(): HasMany { return $this->hasMany(Presence::class); }
    public function getNomCompletAttribute(): string { return trim("{$this->nom} {$this->postnom} {$this->prenom}"); }
}
