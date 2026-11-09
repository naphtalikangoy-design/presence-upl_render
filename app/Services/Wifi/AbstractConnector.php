<?php
namespace App\Services\Wifi;

use App\Models\{PointAcces, SessionReseau};

abstract class AbstractConnector implements WifiConnector
{
    public function capabilities(): array {
        return ['client_rssi' => false, 'tx_power' => false, 'ap_status' => false,
                'session_identity' => false, 'ap_identity' => false, 'terminal_mac' => false];
    }
    public function fetchClientRssi(SessionReseau $session): array { throw new UnsupportedOperationException($this->key() . ' ne fournit pas le RSSI client.'); }
    public function setTxPower(PointAcces $ap, array $settings): array { throw new UnsupportedOperationException($this->key() . ' ne permet pas de modifier la puissance d\'émission.'); }
    public function apStatus(PointAcces $ap): array { throw new UnsupportedOperationException($this->key() . ' ne fournit pas l\'état des points d\'accès.'); }
}
