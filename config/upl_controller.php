<?php
// Connecteur REST générique : AUCUNE API de fabricant n'est supposée. L'administrateur réseau doit fournir
// les URL et la structure JSON de son contrôleur (voir README §9). Secrets uniquement dans .env.
return [
    'base_url' => env('UPL_CTRL_BASE_URL'),
    'token' => env('UPL_CTRL_TOKEN'),
    'auth_header' => env('UPL_CTRL_AUTH_HEADER', 'Authorization'), // ex. "Authorization" (Bearer) ou "X-API-Key"
    'auth_scheme' => env('UPL_CTRL_AUTH_SCHEME', 'Bearer'),        // vide = valeur brute
    'verify_ssl' => env('UPL_CTRL_VERIFY_SSL', true),
    'timeout' => 8,
    'endpoints' => [
        'client_rssi' => env('UPL_CTRL_EP_CLIENT_RSSI'), // GET, placeholders {ip} {username}
        'tx_power' => env('UPL_CTRL_EP_TXPOWER'),        // POST/PUT, placeholder {ap}
        'ap_status' => env('UPL_CTRL_EP_APSTATUS'),      // GET, placeholder {ap}
    ],
    'tx_power_method' => env('UPL_CTRL_TXPOWER_METHOD', 'POST'),
    'mapping' => [
        'readings_path' => env('UPL_CTRL_MAP_READINGS', 'data'), // chemin pointé vers la liste de mesures
        'ap_field' => 'ap_mac', 'rssi_field' => 'rssi', 'band_field' => 'band',
    ],
];
