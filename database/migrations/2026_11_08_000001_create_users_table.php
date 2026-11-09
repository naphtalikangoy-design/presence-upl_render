<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('matricule', 40)->unique();
            $t->string('name');
            $t->string('email')->nullable()->unique();
            $t->string('password');
            $t->enum('role', ['administrateur', 'stagiaire'])->default('stagiaire')->index();
            $t->enum('statut', ['en_attente', 'actif', 'rejete', 'desactive'])->default('en_attente')->index();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary(); $t->string('token'); $t->timestamp('created_at')->nullable();
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary(); $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable(); $t->text('user_agent')->nullable();
            $t->longText('payload'); $t->integer('last_activity')->index();
        });
    }
    public function down(): void {
        Schema::dropIfExists('sessions'); Schema::dropIfExists('password_reset_tokens'); Schema::dropIfExists('users');
    }
};
