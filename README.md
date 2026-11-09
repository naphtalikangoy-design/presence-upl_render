# UPL PRESENCE — plateforme de pointage des stagiaires (Wi-Fi RSSI + GPS)

Application Laravel 11 / MySQL / Blade + Bootstrap 5 / Leaflet / Chart.js, installable (PWA).
Ce dossier est un **overlay** à copier sur une installation Laravel 11 neuve (§6).

> **Statut de vérification.** Le code a été écrit sans PHP ni accès réseau : il n'a **pas été exécuté** et les tests n'ont **pas été lancés**.
> Une vérification statique légère (`python3 tools/check_syntax.py`, équilibre des accolades et directives Blade) est passée.
> **Première chose à faire :** `php artisan migrate --seed` puis `php artisan test`, et corriger les éventuelles erreurs. Aucune intégration réseau réelle n'a été testée (aucun équipement de l'UPL disponible).

## 1. Fonctionnalités livrées

| Domaine | Contenu |
|---|---|
| Public | Accueil (logo configurable), connexion par matricule, inscription (liste officielle ⇒ activation auto, sinon validation admin), mot de passe oublié |
| Stagiaire | Tableau de bord, **pointage arrivée/départ** avec panneau de vérification temps réel, historique + calendrier (vert/rouge/orange/gris), profil limité, notifications |
| Admin | Dashboard + graphiques, stagiaires (CRUD, filtres, valider/rejeter/désactiver), présences (filtres, GPS, correction justifiée, historique, saisie manuelle), facultés/filières/promotions/entreprises, horaires, jours ouvrables, rapports (jour/semaine/mois ; PDF, Excel, impression), journaux, paramètres |
| Localisation intérieure | Bâtiments, Access Points, zones GPS (carte Leaflet/OSM), zones intérieures, configuration Wi-Fi, puissance TX (réelle ou refusée), mesures RSSI, calibration, tests de localisation, état du réseau, historique des connexions |
| Sécurité | Voir §7 |

Rôles : `administrateur` (tout) et `stagiaire` (ses données uniquement) via le middleware `role`.

## 2. Schéma d'intégration

```
Téléphone ──Wi-Fi──> Access Points ──> Contrôleur Wi-Fi / FreeRADIUS
     │                                      │ accounting / API
     │ HTTPS (GPS + nonce)                  │ événements SIGNÉS (HMAC)
     ▼                                      ▼
  Laravel  <──── POST /api/network/events ────  Relais (serveur RADIUS / contrôleur)
     │   sessions_reseau · mesures_rssi   ◄── ou PULL : GenericRestConnector → API du contrôleur
     ▼
  PointageService : compte → limite de tentatives → période → doublon → session Wi-Fi → AP autorisé
                    → RSSI (empreintes) → GPS → nonce à usage unique → INSERT
```
Le navigateur ne fournit que : position GPS (non fiable), type (arrivée/départ) et nonce. SSID, BSSID, MAC et RSSI ne sont jamais lus côté navigateur (aucune API web ne le permet) ni crus s'ils étaient envoyés.

## 3. Règles de décision

Statuts : PRÉSENCE VALIDÉE · WIFI NON AUTORISÉ · ACCESS POINT NON AUTORISÉ · LOCALISATION NON CONFIRMÉE · HORS DU BÂTIMENT · GPS INDISPONIBLE · SIGNAL INSUFFISANT · POINTAGE DÉJÀ EFFECTUÉ (+ COMPTE INACTIF, HORS PÉRIODE, POSITION SUSPECTE, TROP DE TENTATIVES, DEMANDE EXPIRÉE, ARRIVÉE NON ENREGISTRÉE).

- **≥ 2 AP mesurés** : kNN pondéré sur les empreintes du bâtiment ; assurance *élevée* ; confiance ≥ 0,70.
- **1 seul AP** (matériel limité) : vraisemblance gaussienne par classe ; assurance *moyenne* ; confiance plafonnée à 0,65, seuil 0,55 ; **GPS obligatoire**.
- **Aucune mesure RSSI** (ex. FreeRADIUS seul) : politique `strict` ⇒ refus ; politique `ap_gps` ⇒ AP autorisé + GPS, assurance *faible* (tracée).
- Mesure ne ressemblant à aucun point calibré, ou calibration insuffisante (< 5 empreintes/classe) ⇒ non confirmée. RSSI instable ⇒ confiance × 0,85.
- **GPS** : précision ≤ 40 m ⇒ doit être dans le périmètre du *même bâtiment* que l'AP. GPS absent/imprécis (fréquent en intérieur) ⇒ toléré **uniquement** si l'empreinte multi-AP confirme l'intérieur.
- Les seuils se règlent dans **Paramètres** et **Configuration Wi-Fi** ; le mode (`demo`/réel), le connecteur et les secrets restent dans `.env`.

### Limites à connaître
- Le RSSI est **probabiliste** : murs, corps, modèle de téléphone, puissance des AP le font varier. Recalibrer après tout changement d'AP/puissance/canal ; valider avec plusieurs modèles de téléphones.
- La plupart des contrôleurs ne donnent que le RSSI du terminal vu par **l'AP associé**. Le RSSI d'un terminal sur plusieurs AP exige un moteur de localisation du contrôleur ou des rapports de voisinage 802.11k — à vérifier selon le matériel.
- Un navigateur ne peut pas prouver l'authenticité d'une position GPS ; les contrôles de cohérence réduisent la fraude naïve sans l'éliminer.
- Le prêt d'identifiants Wi-Fi à un tiers présent dans le bâtiment n'est pas détectable ; seule une authentification 802.1X individuelle (identité = matricule) limite ce risque.
- `UPL_REQUIRE_SAME_IP` : derrière un NAT/proxy, désactiver si l'IP vue par l'application diffère de celle du NAS.

## 4. Informations à obtenir de l'administrateur réseau de l'UPL

1. Marque/modèle/firmware des AP et du contrôleur ; contrôleur cloud ou local ; licence/API disponible.
2. Authentification Wi-Fi : 802.1X avec User-Name = matricule ? portail captif ? (indispensable pour lier session ↔ stagiaire).
3. FreeRADIUS : version, possibilité d'ajouter un relais d'*accounting* ; attributs reçus (User-Name, Called-Station-Id, Calling-Station-Id, Framed-IP-Address, Acct-Session-Id).
4. Liste des AP : nom, BSSID/MAC, bâtiment, étage, emplacement sur plan.
5. Le contrôleur expose-t-il le **RSSI client** ? sur un ou plusieurs AP ? URL, format JSON, authentification (token/clé API), limites de débit.
6. Puissance TX : endpoint radio, plages autorisées par AP et par bande, droits du compte API.
7. IP/VLAN des clients Wi-Fi vue par le serveur applicatif (NAT ?) ; ouverture pare-feu serveur ⇄ contrôleur.
8. SSID officiel exact et SSID invités à exclure.

Par fabricant (à confirmer dans la documentation de votre firmware ; rien n'est supposé dans le code) : URL de base et authentification de l'API · endpoint « clients » (clé = IP ou MAC ; champs AP + RSSI) · endpoint radio (puissance, canal, bande) · version minimale.
Si le matériel n'expose pas le RSSI : contrôleur compatible localisation, AP en mode scanner, ou politique `ap_gps` (assurance faible).

## 5. Puissance d'émission
`TxPowerService` refuse toute opération si : mode démo, connecteur sans capacité `tx_power`, AP non déclaré compatible (`capacites = {"tx_power":true,"tx_min":5,"tx_max":23}`), valeur hors plage. La valeur n'est enregistrée **que si le matériel confirme** (réponse 2xx). Chaque demande est journalisée. Activation : `UPL_CONNECTOR=generic_rest` + `UPL_CTRL_*` (endpoints fournis par l'administrateur réseau). Pour une marque au format JSON différent, ajouter un adaptateur (étendre `AbstractConnector`, l'enregistrer dans `ConnectorManager`).

## 6. Installation (Laragon / XAMPP — PHP ≥ 8.2, MySQL, Composer)
```bash
composer create-project laravel/laravel upl-presence "^11.0"
cd upl-presence
rm database/migrations/0001_01_01_000000_create_users_table.php tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
# copier le contenu de ce dossier PAR-DESSUS (écrase bootstrap/app.php, bootstrap/providers.php, routes/web.php, tests/TestCase.php)
composer require barryvdh/laravel-dompdf phpoffice/phpspreadsheet
cp .env.example .env && php artisan key:generate
# créer la base "upl_presence" (phpMyAdmin) et ajuster DB_* dans .env
php artisan migrate --seed
php artisan upl:creer-admin          # mot de passe saisi de façon masquée (12 car. min.), jamais dans le code
php artisan serve                    # HTTPS requis pour le GPS : Laragon (SSL auto) ou reverse proxy TLS
```
Ouvrir `/connexion` (admin : matricule choisi à la création) puis `/admin/dashboard`.
En environnement `local`, le seeder crée un bâtiment de démo, 3 AP simulés, des empreintes simulées et 6 comptes `DEMO001…` (mots de passe aléatoires affichés **une seule fois** dans la console).

### Tests
Dans `phpunit.xml`, décommenter `DB_CONNECTION=sqlite` et `DB_DATABASE=:memory:` (extension `pdo_sqlite`), puis `php artisan test`.
Couverts : Haversine, cohérence GPS, classification RSSI (intérieur/extérieur/hors référence/signal faible/instable/1 AP/calibration insuffisante), scénarios A/B/C et variantes du pointage, doublon, rejeu du nonce, webhook signé (valide/falsifié/périmé), contrôle d'accès par rôle, inscription, modules admin, correction historisée, refus de modification de puissance simulée.

### Mode démo
`UPL_NETWORK_MODE=demo` + `UPL_DEMO_SCENARIO=interieur|exterieur|reseau_non_autorise|ap_inconnu|signal_faible|rssi_instable|aucune_donnee`. Bandeau « MODE DÉMO », présences marquées `wifi_verifie=0`, bloqué en production (`APP_ENV=production`). Coordonnées du bâtiment de démo = *placeholders* (à remplacer dans **Zones autorisées**).

### Passage en production réelle
1. `APP_ENV=production`, `APP_DEBUG=false`, `UPL_NETWORK_MODE=reel`, HTTPS, `SESSION_SECURE_COOKIE=true`, SMTP réel, sauvegardes MySQL.
2. `UPL_WEBHOOK_SECRET=$(openssl rand -hex 32)` ; fournir l'URL `/api/network/events` et le secret au relais RADIUS/contrôleur.
3. Déclarer bâtiments, zones GPS (coordonnées relevées sur place), AP (BSSID réels), jours/horaires.
4. **Calibrer** (ci-dessous) puis valider dans *Tests de localisation* avec plusieurs téléphones, à l'intérieur et à l'extérieur.
5. Vérifier *Configuration Wi-Fi → Tester l'intégration* et *État du réseau*.

### Webhook (relais RADIUS / contrôleur)
`POST /api/network/events`, en-têtes `X-UPL-Timestamp` (secondes) et `X-UPL-Signature = hex(HMAC_SHA256(secret, timestamp + "." + corps brut))`, tolérance 5 min. Événements : `session_start|session_update|session_stop|rssi_report` ; champs : `username` (matricule), `session_id`, `ip`, `ssid`, `ap_mac`, `client_mac`, `rssi`, `readings[{ap_mac,rssi,band}]`. Exemple de client signé et outil de test : `tools/send_event_example.py`. La configuration exacte de FreeRADIUS (`rlm_rest`/`rlm_exec` en section accounting) dépend de votre version : à valider avec l'administrateur réseau.

### Calibration (terrain)
Menu **Calibration Wi-Fi** : capture depuis la session d'un téléphone connecté (≥ 3 échantillons récents) ou saisie JSON d'un relevé de site survey. ≥ 5 points intérieur (chaque zone autorisée) et ≥ 5 extérieur (entrée, parking, couloirs, étages voisins). Les empreintes « zone non autorisée » comptent comme extérieur.

## 7. Sécurité en place
Hachage Laravel · CSRF · requêtes paramétrées (Eloquent) · échappement Blade/JS · validation serveur · limitation de connexion (5 essais/5 min), d'inscription, de réinitialisation et de pointage · `role`/`statut` non mass-assignables · accès stagiaire limité à ses propres données · webhook HMAC + fenêtre temporelle · nonce à usage unique (anti-rejeu) · MAC stockée hachée (HMAC) · secrets uniquement dans `.env` ou chiffrés · aucune clé contrôleur côté navigateur · réinitialisation sans énumération de comptes · export Excel protégé contre l'injection de formules · journaux : `journaux_audit`, `journaux_pointage`, `validations_localisation` · mode démo interdit en production · HTTPS forcé en production · service worker sans cache des pages ni du pointage.

## 8. Structure
`app/Services` (GeoService, PointageService, Localisation/, Network/, Wifi/, ReportService, StatsService, AttendanceService) · `app/Http/Controllers/{Auth,Stagiaire,Admin,Api}` · `config/{upl,upl_controller,admin_resources}.php` · `database/migrations` (7 fichiers, 23 tables) · `resources/views` · `public/{css,js,icons,sw.js,manifest.webmanifest}` · `tests/` · `tools/`.

## 9. Déploiement sur Render (Docker + PostgreSQL)
Render n'exécute pas PHP nativement : le projet fournit un `Dockerfile`, `docker/start.sh` et `render.yaml`.
1. Poussez le **contenu de ce dossier** (Dockerfile à la racine du dépôt) sur GitHub.
2. Render → *New* → *Blueprint* → choisissez le dépôt : le blueprint crée le service web Docker **et** la base PostgreSQL `upl-db`.
   (En création manuelle : *Language = Docker*, *Root Directory* = dossier contenant le Dockerfile ou vide, **aucune** commande Build/Start.)
3. Renseignez `APP_KEY` (`php artisan key:generate --show` en local), `APP_URL` (l'URL Render en https), `UPL_ADMIN_MATRICULE`, `UPL_ADMIN_EMAIL`, `UPL_ADMIN_PASSWORD` (12 car., majuscule, minuscule, chiffre, symbole).
4. Au démarrage, `migrate --seed` crée toutes les tables dans PostgreSQL et `upl:creer-admin --env` crée l'administrateur (une seule fois). **Supprimez ensuite `UPL_ADMIN_PASSWORD` dans Render.**
5. `APP_ENV=staging` + `UPL_NETWORK_MODE=demo` : mode démo (données simulées, bâtiment « IAGE (DÉMO) » inclus). `APP_ENV=production` interdit le mode démo.
Limites : un serveur dans le cloud ne voit pas le Wi-Fi de l'UPL ; la validation réelle exige que le relais RADIUS/contrôleur envoie ses événements signés à `https://<votre-url>/api/network/events`. Les LIKE de recherche sont sensibles à la casse sous PostgreSQL.
