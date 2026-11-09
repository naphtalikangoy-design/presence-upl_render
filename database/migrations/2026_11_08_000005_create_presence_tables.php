<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('horaires', function (Blueprint $t) {
            $t->id(); $t->string('nom');
            $t->time('fenetre_debut');   // ouverture du pointage
            $t->time('heure_arrivee');   // heure officielle d'arrivée
            $t->time('heure_limite');    // au-delà : retard
            $t->time('heure_depart');
            $t->time('fenetre_fin');     // fermeture du pointage
            $t->boolean('actif')->default(true); $t->timestamps();
        });
        Schema::create('jours_ouvrables', function (Blueprint $t) {
            $t->id(); $t->unsignedTinyInteger('jour_semaine')->unique(); // 1=lundi ... 7=dimanche
            $t->boolean('actif')->default(true); $t->timestamps();
        });
        Schema::create('presences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stagiaire_id')->constrained('stagiaires')->cascadeOnDelete();
            $t->foreignId('batiment_id')->nullable()->constrained('batiments')->nullOnDelete();
            $t->foreignId('point_acces_id')->nullable()->constrained('points_acces')->nullOnDelete();
            $t->foreignId('session_reseau_id')->nullable()->constrained('sessions_reseau')->nullOnDelete();
            $t->date('date');
            $t->enum('type', ['arrivee', 'depart'])->default('arrivee');
            $t->dateTime('pointe_a');
            $t->decimal('latitude', 10, 7); $t->decimal('longitude', 10, 7);
            $t->float('precision_gps'); $t->float('distance_m');
            $t->enum('statut', ['present', 'retard', 'absent'])->default('present');
            $t->unsignedInteger('retard_minutes')->default(0);
            $t->boolean('wifi_verifie')->default(false);
            $t->string('wifi_source', 20)->nullable();   // radius | controleur | demo | desactive
            $t->boolean('corrige')->default(false);
            $t->text('justification')->nullable();
            $t->timestamps();
            $t->unique(['stagiaire_id', 'date', 'type'], 'presence_unique_par_jour_et_type');
            $t->index('date');
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('titre'); $t->text('message'); $t->timestamp('lu_at')->nullable(); $t->timestamps();
        });
        Schema::create('journaux_audit', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 80)->index();
            $t->string('cible_type', 80)->nullable(); $t->unsignedBigInteger('cible_id')->nullable();
            $t->json('details')->nullable();
            $t->string('ip_address', 45)->nullable(); $t->string('user_agent')->nullable();
            $t->timestamps(); $t->index(['cible_type', 'cible_id']);
        });
    }
    public function down(): void {
        foreach (['journaux_audit','notifications','presences','jours_ouvrables','horaires'] as $x) Schema::dropIfExists($x);
    }
};
