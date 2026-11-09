<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatsService;

class DashboardController extends Controller
{
    public function __invoke(StatsService $stats) { return view('admin.dashboard', ['s' => $stats->dashboard()]); }
}
