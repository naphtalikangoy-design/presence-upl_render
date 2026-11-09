<?php
return [
    // ---- Mode réseau : "demo" (simulation étiquetée, interdit en production) ou "reel" ----
    'network_mode' => env('UPL_NETWORK_MODE', 'demo'),
    // Connecteur réel : freeradius | generic_rest
    'connector' => env('UPL_CONNECTOR', 'freeradius'),
    // Scénario du mode démo : interieur | exterieur | reseau_non_autorise | ap_inconnu | rssi_instable | signal_faible | aucune_donnee
    'demo_scenario' => env('UPL_DEMO_SCENARIO', 'interieur'),

    'wifi_required' => env('UPL_WIFI_REQUIRED', true),
    'allowed_ssid' => env('UPL_ALLOWED_SSID', 'UPL-WIFI'),
    'network_session_ttl_minutes' => (int) env('UPL_NETWORK_SESSION_TTL', 15),
    'require_same_ip' => env('UPL_REQUIRE_SAME_IP', true),

    // Secret HMAC partagé avec le relais RADIUS / contrôleur. JAMAIS exposé au navigateur.
    'webhook_secret' => env('UPL_WEBHOOK_SECRET'),
    'webhook_max_skew_seconds' => 300,

    // ---- Localisation intérieure ----
    // strict  : une confirmation par empreintes RSSI est OBLIGATOIRE (recommandé)
    // ap_gps  : si AUCUNE mesure RSSI n'existe (ex. FreeRADIUS seul), accepte AP autorisé + GPS dans la zone
    //           (niveau d'assurance "faible", tracé comme tel)
    'localisation_policy' => env('UPL_LOCALISATION_POLICY', 'strict'),
    'rssi' => [
        'window_seconds' => 60,
        'min_rssi_dbm' => -80,             // en dessous : SIGNAL_INSUFFISANT
        'min_fingerprints_per_class' => 5, // calibration minimale par classe (intérieur / extérieur)
        'k' => 3,
        'max_reference_distance_db' => 12, // au-delà : la mesure ne ressemble à aucun point calibré
        'unstable_std_db' => 8,
        'min_confidence' => 0.70,          // multi-AP
        'min_confidence_single' => 0.55,   // un seul AP (méthode limitée)
        'single_ap_max_confidence' => 0.65,
        'floor_dbm' => -100,
    ],

    'gps' => [
        'max_accuracy_m' => (int) env('UPL_GPS_MAX_ACCURACY', 40),
        'min_plausible_accuracy_m' => 1.0,
        'max_position_age_seconds' => 120,
        'max_speed_kmh' => 150,
        'strict_accuracy_in_radius' => env('UPL_GPS_STRICT', false),
        // GPS absent/imprécis (fréquent en intérieur) : toléré seulement si l'empreinte multi-AP confirme l'intérieur
        'optional_if_fingerprint' => env('UPL_GPS_OPTIONAL_IF_FINGERPRINT', true),
    ],

    // Création du 1er administrateur au déploiement (Render) : php artisan upl:creer-admin --env
    'admin_bootstrap' => ['matricule' => env('UPL_ADMIN_MATRICULE'), 'nom' => env('UPL_ADMIN_NOM', 'Administrateur UPL'), 'email' => env('UPL_ADMIN_EMAIL'), 'password' => env('UPL_ADMIN_PASSWORD')],

    'anti_fraude' => [
        'challenge_ttl_seconds' => 120,
        'max_refused_attempts' => 8,   // par fenêtre ci-dessous
        'window_minutes' => 10,
    ],
];
