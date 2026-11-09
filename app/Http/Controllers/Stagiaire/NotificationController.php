<?php
namespace App\Http\Controllers\Stagiaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $r)
    {
        $n = $r->user()->notifications()->latest()->paginate(20);
        return view('stagiaire.notifications', ['n' => $n]);
    }
    public function lire(Request $r)
    {
        $r->user()->notifications()->whereNull('lu_at')->update(['lu_at' => now()]);
        return back()->with('ok', 'Notifications marquées comme lues.');
    }
}
