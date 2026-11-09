<?php
namespace App\Services\Wifi;

use App\Models\{PointAcces, SessionReseau};
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Adaptateur REST piloté par configuration (config/upl_controller.php + .env).
 * N'invente aucune API : sans URL fournie par l'administrateur réseau, la capacité correspondante est désactivée.
 * Pour une marque dont le format JSON diffère, créer un nouvel adaptateur (étendre AbstractConnector).
 */
class GenericRestConnector extends AbstractConnector
{
    public function __construct(private array $cfg) {}
    public function key(): string { return 'generic_rest'; }

    public function capabilities(): array {
        $e = $this->cfg['endpoints'] ?? [];
        return array_merge(parent::capabilities(), [
            'client_rssi' => !empty($this->cfg['base_url']) && !empty($e['client_rssi']),
            'tx_power' => !empty($this->cfg['base_url']) && !empty($e['tx_power']),
            'ap_status' => !empty($this->cfg['base_url']) && !empty($e['ap_status']),
        ]);
    }

    private function http() {
        $h = Http::baseUrl($this->cfg['base_url'])->timeout($this->cfg['timeout'] ?? 8)->acceptJson()
            ->withOptions(['verify' => (bool) ($this->cfg['verify_ssl'] ?? true)]);
        $val = trim(($this->cfg['auth_scheme'] ? $this->cfg['auth_scheme'] . ' ' : '') . $this->cfg['token']);
        return $this->cfg['token'] ? $h->withHeaders([$this->cfg['auth_header'] => $val]) : $h;
    }

    public function fetchClientRssi(SessionReseau $s): array {
        if (!$this->capabilities()['client_rssi']) throw new UnsupportedOperationException('Endpoint client_rssi non configuré.');
        $path = strtr($this->cfg['endpoints']['client_rssi'], ['{ip}' => (string) $s->ip_address, '{username}' => rawurlencode($s->username)]);
        $json = $this->http()->get($path)->throw()->json();
        $m = $this->cfg['mapping'];
        $out = [];
        foreach ((array) Arr::get($json, $m['readings_path'], []) as $row) {
            $ap = Arr::get($row, $m['ap_field']); $rssi = Arr::get($row, $m['rssi_field']);
            if ($ap !== null && is_numeric($rssi)) $out[] = ['ap_mac' => (string) $ap, 'rssi' => (int) $rssi, 'band' => Arr::get($row, $m['band_field'])];
        }
        return $out;
    }

    public function setTxPower(PointAcces $ap, array $settings): array {
        if (!$this->capabilities()['tx_power']) throw new UnsupportedOperationException('Endpoint tx_power non configuré.');
        $path = strtr($this->cfg['endpoints']['tx_power'], ['{ap}' => rawurlencode($ap->identifiant)]);
        $resp = $this->http()->send($this->cfg['tx_power_method'] ?? 'POST', $path, ['json' => $settings]);
        // Confirmé seulement si le contrôleur répond 2xx. Une relecture via ap_status est recommandée.
        return ['confirmed' => $resp->successful(), 'applied' => $resp->successful() ? $settings : [], 'raw' => $resp->json()];
    }

    public function apStatus(PointAcces $ap): array {
        if (!$this->capabilities()['ap_status']) throw new UnsupportedOperationException('Endpoint ap_status non configuré.');
        $path = strtr($this->cfg['endpoints']['ap_status'], ['{ap}' => rawurlencode($ap->identifiant)]);
        $r = $this->http()->get($path);
        return ['online' => $r->successful(), 'raw' => $r->json()];
    }
}
