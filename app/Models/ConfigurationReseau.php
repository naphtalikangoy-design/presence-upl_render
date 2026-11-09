<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
/** Paramètres clé/valeur ; les secrets sont chiffrés avec APP_KEY et jamais renvoyés au navigateur. */
class ConfigurationReseau extends Model {
    protected $table = 'configurations_reseau';
    protected $fillable = ['cle','valeur','est_secret'];
    protected $hidden = ['valeur'];
    public static function lire(string $cle, $defaut = null) {
        $c = static::where('cle', $cle)->first();
        if (!$c || $c->valeur === null) return $defaut;
        return $c->est_secret ? Crypt::decryptString($c->valeur) : $c->valeur;
    }
    public static function ecrire(string $cle, ?string $valeur, bool $secret = false): void {
        static::updateOrCreate(['cle' => $cle], [
            'valeur' => ($secret && $valeur !== null) ? Crypt::encryptString($valeur) : $valeur,
            'est_secret' => $secret,
        ]);
    }
}
