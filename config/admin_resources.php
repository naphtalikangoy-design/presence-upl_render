<?php
use App\Models\{Batiment, Faculte};
// Ressources CRUD génériques de l'espace administrateur (voir Admin\ResourceController).
// types : text number time select checkbox textarea json ; {id} dans rules = id courant (règles unique).
return [
    'facultes' => ['model' => Faculte::class, 'titre' => 'Facultés', 'singulier' => 'Faculté', 'colonnes' => ['nom', 'code'],
        'champs' => [['nom', 'Nom', 'text', 'required|max:255|unique:facultes,nom,{id}'], ['code', 'Code', 'text', 'nullable|max:20']]],
    'filieres' => ['model' => \App\Models\Filiere::class, 'titre' => 'Filières', 'singulier' => 'Filière', 'with' => ['faculte'], 'colonnes' => ['faculte.nom', 'nom'],
        'champs' => [['faculte_id', 'Faculté', 'select', 'required|exists:facultes,id', ['model' => Faculte::class, 'label' => 'nom']], ['nom', 'Nom', 'text', 'required|max:255']]],
    'promotions' => ['model' => \App\Models\Promotion::class, 'titre' => 'Promotions', 'singulier' => 'Promotion', 'colonnes' => ['nom'],
        'champs' => [['nom', 'Nom', 'text', 'required|max:100|unique:promotions,nom,{id}']]],
    'entreprises' => ['model' => \App\Models\EntrepriseAccueil::class, 'titre' => 'Entreprises / institutions d\'accueil', 'singulier' => 'Entreprise', 'colonnes' => ['nom', 'adresse'],
        'champs' => [['nom', 'Nom', 'text', 'required|max:255|unique:entreprises_accueil,nom,{id}'], ['adresse', 'Adresse', 'text', 'nullable|max:255']]],
    'batiments' => ['model' => Batiment::class, 'titre' => 'Bâtiments', 'singulier' => 'Bâtiment', 'colonnes' => ['nom', 'code', 'actif', 'pointage_actif'],
        'champs' => [['nom', 'Nom', 'text', 'required|max:255|unique:batiments,nom,{id}'], ['code', 'Code', 'text', 'nullable|max:20'],
            ['description', 'Description', 'textarea', 'nullable|max:1000'], ['actif', 'Bâtiment actif', 'checkbox', 'boolean'],
            ['pointage_actif', 'Pointage activé dans ce bâtiment', 'checkbox', 'boolean']]],
    'points-acces' => ['model' => \App\Models\PointAcces::class, 'titre' => 'Access Points Wi-Fi', 'singulier' => 'Access Point', 'with' => ['batiment'],
        'colonnes' => ['nom', 'batiment.nom', 'identifiant', 'marque', 'etage', 'actif', 'derniere_vue_at'],
        'champs' => [['batiment_id', 'Bâtiment', 'select', 'required|exists:batiments,id', ['model' => Batiment::class, 'label' => 'nom']],
            ['nom', 'Nom', 'text', 'required|max:255'], ['identifiant', 'Identifiant matériel (MAC/BSSID)', 'text', 'required|size:12|unique:points_acces,identifiant,{id}'],
            ['marque', 'Marque', 'text', 'nullable|max:40'], ['modele', 'Modèle', 'text', 'nullable|max:60'], ['etage', 'Étage', 'text', 'nullable|max:20'],
            ['ip_gestion', 'IP de gestion', 'text', 'nullable|ip'], ['plan_x', 'Position X sur le plan (m)', 'number', 'nullable|numeric'], ['plan_y', 'Position Y sur le plan (m)', 'number', 'nullable|numeric'],
            ['canal_2g', 'Canal 2,4 GHz (informatif)', 'number', 'nullable|integer|between:1,14'], ['canal_5g', 'Canal 5 GHz (informatif)', 'number', 'nullable|integer|between:32,177'],
            ['capacites', 'Capacités (JSON, ex. {"tx_power":true,"tx_min":5,"tx_max":23})', 'json', 'nullable|json'], ['actif', 'Actif', 'checkbox', 'boolean']]],
    'horaires' => ['model' => \App\Models\Horaire::class, 'titre' => 'Horaires de pointage', 'singulier' => 'Horaire', 'colonnes' => ['nom', 'fenetre_debut', 'heure_arrivee', 'heure_limite', 'heure_depart', 'fenetre_fin', 'actif'],
        'champs' => [['nom', 'Nom', 'text', 'required|max:100'], ['fenetre_debut', 'Ouverture du pointage', 'time', 'required|date_format:H:i,H:i:s'],
            ['heure_arrivee', 'Heure d\'arrivée', 'time', 'required|date_format:H:i,H:i:s'], ['heure_limite', 'Heure limite (retard au-delà)', 'time', 'required|date_format:H:i,H:i:s'],
            ['heure_depart', 'Heure de départ', 'time', 'required|date_format:H:i,H:i:s'], ['fenetre_fin', 'Fermeture du pointage', 'time', 'required|date_format:H:i,H:i:s'],
            ['actif', 'Horaire actif (le plus récent actif s\'applique)', 'checkbox', 'boolean']]],
];
