<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('facultes', function (Blueprint $t) {
            $t->id(); $t->string('nom')->unique(); $t->string('code', 20)->nullable(); $t->timestamps();
        });
        Schema::create('filieres', function (Blueprint $t) {
            $t->id(); $t->foreignId('faculte_id')->constrained('facultes')->cascadeOnDelete();
            $t->string('nom'); $t->timestamps(); $t->unique(['faculte_id', 'nom']);
        });
        Schema::create('promotions', function (Blueprint $t) {
            $t->id(); $t->string('nom')->unique(); $t->timestamps();
        });
        Schema::create('entreprises_accueil', function (Blueprint $t) {
            $t->id(); $t->string('nom')->unique(); $t->string('adresse')->nullable(); $t->timestamps();
        });
        // Liste officielle optionnelle fournie par l'administration (vérification du matricule)
        Schema::create('matricules_officiels', function (Blueprint $t) {
            $t->id(); $t->string('matricule', 40)->unique(); $t->string('nom_complet')->nullable(); $t->timestamps();
        });
    }
    public function down(): void {
        foreach (['matricules_officiels','entreprises_accueil','promotions','filieres','facultes'] as $x) Schema::dropIfExists($x);
    }
};
