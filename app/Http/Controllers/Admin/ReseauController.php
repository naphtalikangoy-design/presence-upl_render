<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ConfigurationReseau, JournalAudit, PointAcces, SessionReseau};
use App\Services\Wifi\{ConnectorManager, UnsupportedOperationException};
use Illuminate\Http\Request;

class ReseauController extends Controller
{
    public function show(ConnectorManager $cm)
    {
        $c = $cm->driver();
        return view('admin.reseau.config', ['connecteur' => $c->key(), 'caps' => $c->capabilities(), 'mode' => config('upl.network_mode'),
            'secretOk' => (bool) config('upl.webhook_secret'), 'ctrlOk' => (bool) config('upl_controller.base_url'), 'webhook' => url('/api/network/events'),
            'derniere' => SessionReseau::where('est_demo', false)->latest('derniere_activite_at')->first()]);
    }

    public function update(Request $r)
    {
        $d = $r->validate(['allowed_ssid' => 'required|string|max:64', 'network_session_ttl_minutes' => 'required|integer|between:1,240',
            'localisation_policy' => 'required|in:strict,ap_gps']);
        foreach ($d as $k => $v) ConfigurationReseau::ecrire($k, (string) $v);
        ConfigurationReseau::ecrire('wifi_required', $r->boolean('wifi_required') ? '1' : '0');
        ConfigurationReseau::ecrire('require_same_ip', $r->boolean('require_same_ip') ? '1' : '0');
        JournalAudit::noter('admin.reseau.configuration', $r, null, $d + ['wifi_required' => $r->boolean('wifi_required'), 'require_same_ip' => $r->boolean('require_same_ip')]);
        return back()->with('ok', 'Configuration réseau enregistrée.');
    }

    /** Test RÉEL de l'intégration (jamais de succès simulé). */
    public function tester(Request $r, ConnectorManager $cm)
    {
        $out = []; $c = $cm->driver();
        $out[] = [$c->key() === 'demo' ? 'warn' : 'ok', 'Connecteur actif : ' . $c->key() . ($c->key() === 'demo' ? ' (SIMULATION — aucune intégration réelle testée)' : '')];
        $out[] = [config('upl.webhook_secret') ? 'ok' : 'ko', config('upl.webhook_secret') ? 'Secret webhook configuré (.env).' : 'UPL_WEBHOOK_SECRET absent : le webhook /api/network/events est désactivé (503).'];
        $recent = SessionReseau::where('est_demo', false)->where('derniere_activite_at', '>=', now()->subMinutes(config('upl.network_session_ttl_minutes')))->count();
        $out[] = [$recent ? 'ok' : 'ko', "$recent session(s) réelle(s) active(s) reçue(s) de l'infrastructure sur les " . config('upl.network_session_ttl_minutes') . ' dernières minutes.'];
        $vus = PointAcces::where('actif', true)->where('derniere_vue_at', '>=', now()->subMinutes(15))->count();
        $out[] = [$vus ? 'ok' : 'ko', "$vus Access Point(s) actif(s) vu(s) dans les 15 dernières minutes sur " . PointAcces::where('actif', true)->count() . ' déclaré(s).'];
        foreach (['client_rssi' => 'RSSI client', 'tx_power' => 'Contrôle de puissance', 'ap_status' => 'État des AP'] as $k => $l)
            $out[] = [($c->capabilities()[$k] ?? false) ? 'ok' : 'warn', "$l : " . (($c->capabilities()[$k] ?? false) ? 'supporté par le connecteur' : 'NON supporté par le connecteur actif')];
        if ($c->capabilities()['ap_status'] ?? false) {
            $ap = PointAcces::where('actif', true)->first();
            if ($ap) { try { $s = $c->apStatus($ap); $out[] = [$s['online'] ? 'ok' : 'ko', "Contrôleur — état de {$ap->nom} : " . ($s['online'] ? 'en ligne' : 'hors ligne')]; }
                catch (UnsupportedOperationException | \Throwable $e) { $out[] = ['ko', 'Contrôleur injoignable : ' . $e->getMessage()]; } }
        }
        JournalAudit::noter('admin.reseau.test', $r, null, ['connecteur' => $c->key()]);
        return back()->with('tests', $out);
    }
}
