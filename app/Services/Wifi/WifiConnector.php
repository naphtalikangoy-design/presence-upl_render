<?php
namespace App\Services\Wifi;

use App\Models\{PointAcces, SessionReseau};

interface WifiConnector
{
    public function key(): string;

    /** @return array<string,bool> client_rssi, tx_power, ap_status, session_identity, ap_identity, terminal_mac */
    public function capabilities(): array;

    /** @return array<int,array{ap_mac:string,rssi:int,band:?string}> mesures RSSI du terminal de la session */
    public function fetchClientRssi(SessionReseau $session): array;

    /** @return array{confirmed:bool,applied:array,raw?:mixed} — confirmed=true UNIQUEMENT si le matériel l'a confirmé */
    public function setTxPower(PointAcces $ap, array $settings): array;

    /** @return array{online:bool,raw?:mixed} */
    public function apStatus(PointAcces $ap): array;
}
