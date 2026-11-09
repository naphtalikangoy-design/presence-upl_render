<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
class JournalAudit extends Model {
    protected $table = 'journaux_audit';
    protected $guarded = ['id'];
    protected $casts = ['details' => 'array'];
    public function user() { return $this->belongsTo(User::class); }
    public static function noter(string $action, ?Request $r = null, $cible = null, array $details = []): self {
        $r ??= request();
        return static::create([
            'user_id' => $r->user()?->id, 'action' => $action,
            'cible_type' => $cible ? class_basename($cible) : null, 'cible_id' => $cible?->id,
            'details' => $details ?: null, 'ip_address' => $r->ip(),
            'user_agent' => mb_substr((string) $r->userAgent(), 0, 250),
        ]);
    }
}
