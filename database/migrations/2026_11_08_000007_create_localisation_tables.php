<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('zones_interieures', function (Blueprint $t) {
            $t->id(); $t->foreignId('batiment_id')->constrained('batiments')->cascadeOnDelete();
            $t->string('nom'); $t->string('etage', 20)->nullable(); $t->boolean('autorise')->default(true); $t->timestamps();
        });
        Schema::create('mesures_rssi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('session_reseau_id')->nullable()->constrained('sessions_reseau')->nullOnDelete();
            $t->foreignId('point_acces_id')->nullable()->constrained('points_acces')->nullOnDelete();
            $t->string('ap_identifiant', 64);
            $t->string('terminal_mac_hash', 64);          // HMAC de la MAC : la MAC brute n'est jamais stockée
            $t->smallInteger('rssi_dbm'); $t->string('bande', 8)->nullable();
            $t->timestamp('mesure_at'); $t->string('source', 20); $t->boolean('est_demo')->default(false);
            $t->timestamps(); $t->index(['terminal_mac_hash', 'mesure_at']);
        });
        Schema::create('empreintes_wifi', function (Blueprint $t) {
            $t->id(); $t->foreignId('batiment_id')->constrained('batiments')->cascadeOnDelete();
            $t->foreignId('zone_interieure_id')->nullable()->constrained('zones_interieures')->nullOnDelete();
            $t->enum('classe', ['interieur', 'exterieur']);
            $t->json('signature');                         // {"<ap_identifiant>": rssi_median_dbm, ...}
            $t->unsignedSmallInteger('nb_echantillons')->default(1);
            $t->string('appareil', 80)->nullable();
            $t->foreignId('releve_par')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('releve_at')->nullable(); $t->boolean('actif')->default(true); $t->boolean('est_demo')->default(false);
            $t->timestamps(); $t->index(['batiment_id', 'classe', 'actif']);
        });
        Schema::create('validations_localisation', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('stagiaire_id')->nullable()->constrained('stagiaires')->nullOnDelete();
            $t->foreignId('session_reseau_id')->nullable()->constrained('sessions_reseau')->nullOnDelete();
            $t->foreignId('batiment_id')->nullable()->constrained('batiments')->nullOnDelete();
            $t->string('statut', 40); $t->string('methode', 30)->nullable(); $t->string('classe', 20)->nullable();
            $t->float('confiance')->nullable(); $t->string('assurance', 10)->nullable(); $t->boolean('gps_verifie')->default(false);
            $t->json('details')->nullable(); $t->boolean('est_demo')->default(false);
            $t->timestamps(); $t->index(['user_id', 'created_at']);
        });
        Schema::create('journaux_pointage', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('stagiaire_id')->nullable()->constrained('stagiaires')->nullOnDelete();
            $t->string('statut', 40)->index(); $t->string('ip_address', 45)->nullable(); $t->json('details')->nullable();
            $t->timestamps(); $t->index(['user_id', 'created_at']);
        });
        Schema::create('defis_pointage', function (Blueprint $t) {   // anti-rejeu : nonce à usage unique
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('nonce', 64)->unique(); $t->timestamp('expire_at'); $t->timestamp('utilise_at')->nullable(); $t->timestamps();
        });
    }
    public function down(): void {
        foreach (['defis_pointage','journaux_pointage','validations_localisation','empreintes_wifi','mesures_rssi','zones_interieures'] as $x) Schema::dropIfExists($x);
    }
};
