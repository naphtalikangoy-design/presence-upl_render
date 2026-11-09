<?php
namespace App\Services\Wifi;

use App\Models\{PointAcces, SessionReseau};

/** MODE DÉMO : données SIMULÉES. Ne jamais traiter comme une mesure réelle. N'implémente volontairement
 *  PAS setTxPower : une modification de puissance ne se simule pas. */
class DemoConnector extends AbstractConnector
{
    public function key(): string { return 'demo'; }
    public function capabilities(): array {
        return array_merge(parent::capabilities(), ['client_rssi' => true, 'session_identity' => true, 'ap_identity' => true, 'terminal_mac' => true]);
    }

    public function fetchClientRssi(SessionReseau $s): array {
        $aps = PointAcces::where('actif', true)->orderBy('id')->limit(3)->get();
        $base = match (config('upl.demo_scenario')) {
            'exterieur' => [-76, -83, -89],
            'signal_faible' => [-88, -91, -93],
            'rssi_instable' => [-45, -57, -68],
            'aucune_donnee' => [],
            default => [-45, -57, -68],
        };
        $out = [];
        foreach ($aps as $i => $ap) {
            if (!isset($base[$i])) continue;
            $samples = config('upl.demo_scenario') === 'rssi_instable'
                ? [$base[$i], $base[$i] - 25, $base[$i] + 3, $base[$i] - 28, $base[$i] + 1] : [$base[$i]];
            foreach ($samples as $v) $out[] = ['ap_mac' => $ap->identifiant, 'rssi' => $v, 'band' => '5'];
        }
        return $out;
    }
}
