<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('batiments', function (Blueprint $t) { $t->boolean('pointage_actif')->default(true); });
        Schema::table('points_acces', function (Blueprint $t) {
            $t->string('marque', 40)->nullable(); $t->string('modele', 60)->nullable(); $t->string('ip_gestion', 45)->nullable();
            $t->string('etage', 20)->nullable(); $t->decimal('plan_x', 6, 2)->nullable(); $t->decimal('plan_y', 6, 2)->nullable();
            $t->smallInteger('puissance_dbm')->nullable(); $t->unsignedSmallInteger('canal_2g')->nullable(); $t->unsignedSmallInteger('canal_5g')->nullable();
            $t->json('capacites')->nullable();   // ex. {"client_rssi":true,"tx_power":true,"tx_min":5,"tx_max":23}
            $t->timestamp('derniere_vue_at')->nullable();
        });
        Schema::table('sessions_reseau', function (Blueprint $t) { $t->string('terminal_mac_hash', 64)->nullable()->index(); });
    }
    public function down(): void {
        Schema::table('sessions_reseau', fn (Blueprint $t) => $t->dropColumn('terminal_mac_hash'));
        Schema::table('points_acces', fn (Blueprint $t) => $t->dropColumn(['marque','modele','ip_gestion','etage','plan_x','plan_y','puissance_dbm','canal_2g','canal_5g','capacites','derniere_vue_at']));
        Schema::table('batiments', fn (Blueprint $t) => $t->dropColumn('pointage_actif'));
    }
};
