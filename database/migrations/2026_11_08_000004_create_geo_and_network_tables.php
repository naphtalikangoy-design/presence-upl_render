<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('batiments', function (Blueprint $t) {
            $t->id(); $t->string('nom')->unique(); $t->string('code', 20)->nullable();
            $t->text('description')->nullable(); $t->boolean('actif')->default(true); $t->timestamps();
        });
        Schema::create('zones_geographiques', function (Blueprint $t) {
            $t->id(); $t->foreignId('batiment_id')->constrained('batiments')->cascadeOnDelete();
            $t->string('nom');
            $t->decimal('latitude', 10, 7); $t->decimal('longitude', 10, 7);
            $t->unsignedInteger('rayon_m')->default(50);
            $t->boolean('actif')->default(true); $t->timestamps();
        });
        Schema::create('points_acces', function (Blueprint $t) {
            $t->id(); $t->foreignId('batiment_id')->constrained('batiments')->cascadeOnDelete();
            $t->string('nom');
            $t->string('identifiant', 64)->unique(); // BSSID / Called-Station-Id normalisé (aabbccddeeff)
            $t->boolean('actif')->default(true); $t->timestamps();
        });
        Schema::create('configurations_reseau', function (Blueprint $t) {
            $t->id(); $t->string('cle')->unique(); $t->text('valeur')->nullable();
            $t->boolean('est_secret')->default(false); $t->timestamps();
        });
        Schema::create('sessions_reseau', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('username', 80)->index();          // User-Name RADIUS (matricule)
            $t->string('session_id_externe', 80)->nullable()->index(); // Acct-Session-Id
            $t->string('ip_address', 45)->nullable()->index();
            $t->string('ssid', 64)->nullable();
            $t->string('ap_identifiant', 64)->nullable()->index();
            $t->foreignId('point_acces_id')->nullable()->constrained('points_acces')->nullOnDelete();
            $t->enum('source', ['radius', 'controleur', 'demo'])->default('radius');
            $t->boolean('est_demo')->default(false);
            $t->timestamp('debut_at')->nullable(); $t->timestamp('derniere_activite_at')->nullable()->index();
            $t->timestamp('fin_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        foreach (['sessions_reseau','configurations_reseau','points_acces','zones_geographiques','batiments'] as $x) Schema::dropIfExists($x);
    }
};
