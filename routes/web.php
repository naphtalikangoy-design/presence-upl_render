<?php
use App\Http\Controllers\Admin as A;
use App\Http\Controllers\Auth\{AuthController, PasswordResetController, RegisterController};
use App\Http\Controllers\Stagiaire as S;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// ---------- Public ----------
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('/inscription', [RegisterController::class, 'show']);
    Route::post('/inscription', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'forgot']);
    Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'send'])->middleware('throttle:5,1');
    Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'form'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'reset'])->middleware('throttle:5,1');
});
Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Espace stagiaire ----------
Route::middleware(['auth', 'role:stagiaire'])->prefix('stagiaire')->group(function () {
    Route::get('/dashboard', S\DashboardController::class);
    Route::get('/pointage', [S\PointageController::class, 'index'])->name('pointage');
    Route::post('/pointage/verifier', [S\PointageController::class, 'verifier'])->middleware('throttle:20,1');
    Route::post('/pointage/pointer', [S\PointageController::class, 'pointer'])->middleware('throttle:6,1');
    Route::get('/historique', S\HistoriqueController::class);
    Route::get('/profil', [S\ProfilController::class, 'show']);
    Route::put('/profil', [S\ProfilController::class, 'update']);
    Route::get('/notifications', [S\NotificationController::class, 'index']);
    Route::post('/notifications/lire', [S\NotificationController::class, 'lire']);
});

// ---------- Espace administrateur ----------
Route::middleware(['auth', 'role:administrateur'])->prefix('admin')->group(function () {
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', A\DashboardController::class);

    Route::get('/stagiaires', [A\StagiaireController::class, 'index']);
    Route::get('/stagiaires/create', [A\StagiaireController::class, 'create']);
    Route::post('/stagiaires', [A\StagiaireController::class, 'store']);
    Route::get('/stagiaires/{stagiaire}', [A\StagiaireController::class, 'show']);
    Route::get('/stagiaires/{stagiaire}/edit', [A\StagiaireController::class, 'edit']);
    Route::put('/stagiaires/{stagiaire}', [A\StagiaireController::class, 'update']);
    Route::delete('/stagiaires/{stagiaire}', [A\StagiaireController::class, 'destroy']);
    Route::post('/stagiaires/{stagiaire}/valider', [A\StagiaireController::class, 'valider']);
    Route::post('/stagiaires/{stagiaire}/rejeter', [A\StagiaireController::class, 'rejeter']);
    Route::post('/stagiaires/{stagiaire}/reactiver', [A\StagiaireController::class, 'reactiver']);

    Route::get('/presences', [A\PresenceController::class, 'index']);
    Route::get('/presences/create', [A\PresenceController::class, 'create']);
    Route::post('/presences', [A\PresenceController::class, 'store']);
    Route::get('/presences/{presence}/edit', [A\PresenceController::class, 'edit']);
    Route::put('/presences/{presence}', [A\PresenceController::class, 'update']);

    Route::get('/zones', [A\ZoneController::class, 'index']);
    Route::post('/zones', [A\ZoneController::class, 'store']);
    Route::put('/zones/{zone}', [A\ZoneController::class, 'update']);
    Route::delete('/zones/{zone}', [A\ZoneController::class, 'destroy']);
    Route::post('/zones-interieures', [A\ZoneController::class, 'storeInterieure']);
    Route::delete('/zones-interieures/{zi}', [A\ZoneController::class, 'destroyInterieure']);

    Route::get('/configuration-reseau', [A\ReseauController::class, 'show']);
    Route::put('/configuration-reseau', [A\ReseauController::class, 'update']);
    Route::post('/configuration-reseau/tester', [A\ReseauController::class, 'tester']);

    Route::get('/mesures-rssi', [A\LocalisationController::class, 'mesures']);
    Route::get('/connexions', [A\LocalisationController::class, 'connexions']);
    Route::get('/etat-reseau', [A\LocalisationController::class, 'etat']);
    Route::get('/calibration', [A\LocalisationController::class, 'calibration']);
    Route::post('/calibration', [A\LocalisationController::class, 'calibrer']);
    Route::patch('/calibration/{empreinte}', [A\LocalisationController::class, 'basculer']);
    Route::delete('/calibration/{empreinte}', [A\LocalisationController::class, 'supprimer']);
    Route::get('/tests-localisation', [A\LocalisationController::class, 'test']);
    Route::post('/tests-localisation', [A\LocalisationController::class, 'tester']);
    Route::get('/puissance', [A\LocalisationController::class, 'puissance']);
    Route::post('/puissance/{pointAcces}', [A\LocalisationController::class, 'appliquerPuissance']);

    Route::get('/rapports', [A\RapportController::class, 'index']);
    Route::get('/rapports/pdf', [A\RapportController::class, 'pdf']);
    Route::get('/rapports/excel', [A\RapportController::class, 'excel']);
    Route::get('/rapports/imprimer', [A\RapportController::class, 'imprimer']);

    Route::get('/journaux', A\JournalController::class);
    Route::get('/parametres', [A\ParametresController::class, 'show']);
    Route::put('/parametres/jours', [A\ParametresController::class, 'jours']);
    Route::put('/parametres/regles', [A\ParametresController::class, 'regles']);
    Route::post('/parametres/logo', [A\ParametresController::class, 'logo']);
    Route::post('/parametres/matricules', [A\ParametresController::class, 'importer']);
    Route::delete('/parametres/matricules', [A\ParametresController::class, 'viderListe']);

    // CRUD génériques (config/admin_resources.php) — déclarés en dernier
    $slugs = implode('|', array_keys(config('admin_resources')));
    Route::get('/{slug}', [A\ResourceController::class, 'index'])->where('slug', $slugs);
    Route::post('/{slug}', [A\ResourceController::class, 'store'])->where('slug', $slugs);
    Route::get('/{slug}/{id}/edit', [A\ResourceController::class, 'edit'])->where('slug', $slugs)->whereNumber('id');
    Route::put('/{slug}/{id}', [A\ResourceController::class, 'update'])->where('slug', $slugs)->whereNumber('id');
    Route::delete('/{slug}/{id}', [A\ResourceController::class, 'destroy'])->where('slug', $slugs)->whereNumber('id');
});
