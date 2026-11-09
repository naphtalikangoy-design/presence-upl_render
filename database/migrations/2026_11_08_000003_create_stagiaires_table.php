<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('stagiaires', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('nom'); $t->string('postnom'); $t->string('prenom');
            $t->enum('sexe', ['M', 'F']);
            $t->foreignId('faculte_id')->constrained('facultes');
            $t->foreignId('filiere_id')->constrained('filieres');
            $t->foreignId('promotion_id')->constrained('promotions');
            $t->foreignId('entreprise_id')->nullable()->constrained('entreprises_accueil')->nullOnDelete();
            $t->string('telephone', 30);
            $t->string('photo')->nullable();
            $t->date('date_debut_stage')->nullable();
            $t->date('date_fin_stage')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('stagiaires'); }
};
