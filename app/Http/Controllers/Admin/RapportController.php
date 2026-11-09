<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Batiment, Faculte, JournalAudit};
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    public function __construct(private ReportService $svc) {}

    private function build(Request $r): array
    {
        $d = $r->validate(['type' => 'nullable|in:jour,semaine,mois', 'date' => 'nullable|date', 'faculte_id' => 'nullable|exists:facultes,id', 'batiment_id' => 'nullable|exists:batiments,id']);
        return $this->svc->build($d['type'] ?? 'jour', Carbon::parse($d['date'] ?? now()), $d['faculte_id'] ?? null, $d['batiment_id'] ?? null);
    }

    public function index(Request $r)
    {
        return view('admin.rapports.index', ['rap' => $this->build($r), 'facultes' => Faculte::orderBy('nom')->get(), 'batiments' => Batiment::orderBy('nom')->get()]);
    }

    public function imprimer(Request $r) { return view('admin.rapports.print', ['rap' => $this->build($r)]); }

    public function pdf(Request $r)
    {
        $rap = $this->build($r); JournalAudit::noter('admin.rapport.pdf', $r, null, ['titre' => $rap['titre']]);
        return \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.rapports.print', ['rap' => $rap, 'pdf' => true])->setPaper('a4', 'landscape')->download('rapport-presences-' . $rap['debut']->format('Ymd') . '.pdf');
    }

    public function excel(Request $r)
    {
        $rap = $this->build($r); JournalAudit::noter('admin.rapport.excel', $r, null, ['titre' => $rap['titre']]);
        $safe = fn ($v) => (is_string($v) && preg_match('/^[=+\-@]/', $v)) ? "'" . $v : $v;      // anti-injection de formules
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet(); $sh = $ss->getActiveSheet(); $sh->setTitle('Rapport');
        $sh->setCellValue('A1', $rap['titre']);
        $sh->fromArray([$rap['entetes']], null, 'A3');
        $sh->fromArray(array_map(fn ($l) => array_map($safe, $l), $rap['lignes']), null, 'A4');
        $sh->getStyle('A3:' . $sh->getHighestColumn() . '3')->getFont()->setBold(true);
        foreach (range('A', $sh->getHighestColumn()) as $c) $sh->getColumnDimension($c)->setAutoSize(true);
        $tmp = tempnam(sys_get_temp_dir(), 'rap'); (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($tmp);
        return response()->download($tmp, 'rapport-presences-' . $rap['debut']->format('Ymd') . '.xlsx')->deleteFileAfterSend(true);
    }
}
