<?php
namespace App\Services\Wifi;

/**
 * FreeRADIUS (accounting) — connecteur en mode PUSH : les événements arrivent sur /api/network/events.
 * Fournit : identité de session (User-Name), AP (Called-Station-Id), MAC terminal (Calling-Station-Id), IP, début/fin.
 * Ne fournit PAS de RSSI (aucun attribut RADIUS standard) ni de contrôle de puissance.
 * Si le RSSI est nécessaire, utiliser un contrôleur compatible via generic_rest ou un relais qui pousse des "rssi_report".
 */
class FreeRadiusConnector extends AbstractConnector
{
    public function key(): string { return 'freeradius'; }
    public function capabilities(): array {
        return array_merge(parent::capabilities(), ['session_identity' => true, 'ap_identity' => true, 'terminal_mac' => true]);
    }
}
