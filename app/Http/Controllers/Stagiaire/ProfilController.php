<?php
namespace App\Http\Controllers\Stagiaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Auth};
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function show(Request $r) { return view('stagiaire.profil', ['u' => $r->user(), 'st' => $r->user()->stagiaire->load('faculte', 'filiere', 'promotion', 'entreprise')]); }

    /** Modification limitée : téléphone, e-mail, photo, mot de passe. Identité et matricule ne sont pas modifiables. */
    public function update(Request $r)
    {
        $u = $r->user(); $st = $u->stagiaire;
        $d = $r->validate(['telephone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s().-]{6,30}$/'], 'email' => 'required|email|max:255|unique:users,email,' . $u->id,
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 'password_actuel' => 'nullable|required_with:password|current_password',
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()]]);
        if ($r->hasFile('photo')) {
            $name = 'p' . $st->id . '_' . bin2hex(random_bytes(6)) . '.' . $r->file('photo')->extension();
            $r->file('photo')->move(public_path('uploads/photos'), $name);
            $st->photo = 'uploads/photos/' . $name;
        }
        $st->telephone = $d['telephone']; $st->save();
        $u->email = $d['email'];
        if (!empty($d['password'])) $u->password = $d['password'];
        $u->save();
        return back()->with('ok', 'Profil mis à jour.');
    }
}
