<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{JournalAudit, JournalPointage, ValidationLocalisation};
use Illuminate\Http\Request;

class JournalController extends Controller
{
    public function __invoke(Request $r)
    {
        $tab = in_array($r->query('tab'), ['pointage', 'validations']) ? $r->query('tab') : 'audit';
        $rows = match ($tab) {
            'pointage' => JournalPointage::with('user')->when($r->filled('q'), fn ($q) => $q->where('statut', 'like', '%' . $r->q . '%'))->latest()->paginate(40),
            'validations' => ValidationLocalisation::with('user', 'batiment')->when($r->filled('q'), fn ($q) => $q->where('statut', 'like', '%' . $r->q . '%'))->latest()->paginate(40),
            default => JournalAudit::with('user')->when($r->filled('q'), fn ($q) => $q->where('action', 'like', '%' . $r->q . '%'))->latest()->paginate(40),
        };
        return view('admin.journaux', ['rows' => $rows->withQueryString(), 'tab' => $tab]);
    }
}
