<?php
namespace App\Http\Controllers\Stagiaire;

use App\Http\Controllers\Controller;
use App\Services\PointageService;
use Illuminate\Http\Request;

class PointageController extends Controller
{
    public function __construct(private PointageService $svc) {}

    public function index(Request $r) { return view('stagiaire.pointage', ['demo' => config('upl.network_mode') === 'demo', 'stagiaire' => $r->user()->stagiaire]); }

    private function input(Request $r): array
    {
        $v = $r->validate(['type' => 'nullable|in:arrivee,depart', 'position' => 'nullable|array', 'position.latitude' => 'required_with:position|numeric',
            'position.longitude' => 'required_with:position|numeric', 'position.accuracy' => 'required_with:position|numeric|min:0',
            'position.timestamp' => 'nullable|integer', 'nonce' => 'nullable|string|max:64']);
        return [$v['position'] ?? null, $v['type'] ?? 'arrivee'];
    }

    private function json(array $x, array $extra = [])
    {
        return response()->json(array_merge(['ok' => $x['ok'], 'statut' => $x['statut']->value, 'label' => $x['statut']->label(), 'message' => $x['message'],
            'checks' => $x['checks'], 'demo' => config('upl.network_mode') === 'demo'], $extra), $x['ok'] ? 200 : 422);
    }

    /** Diagnostic temps réel : n'enregistre rien. Si tout est OK, émet un nonce à usage unique. */
    public function verifier(Request $r)
    {
        [$pos, $type] = $this->input($r);
        $x = $this->svc->evaluate($r->user(), $r, $pos, $type);
        return $this->json($x, $x['ok'] ? ['nonce' => $this->svc->issueChallenge($r->user())] : []);
    }

    public function pointer(Request $r)
    {
        [$pos, $type] = $this->input($r);
        $x = $this->svc->record($r->user(), $r, $pos, $r->input('nonce'), $type);
        return $this->json($x, ['statut_presence' => $x['presence']->statut ?? null]);
    }
}
