<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = in_array($request->query('period'), ['week', 'month', 'quarter', 'year'], true)
            ? $request->query('period')
            : 'week';
        $periodStarts = [
            'week' => now()->subDays(7),
            'month' => now()->subMonth(),
            'quarter' => now()->subMonths(3),
            'year' => now()->subYear(),
        ];

        $userTransfers = Transfer::query()
            ->ownedBy($request->user())
            ->where('created_at', '>=', $periodStarts[$period]);

        $transfers = (clone $userTransfers)
            ->latest()
            ->limit(10)
            ->get();

        $totalTransfers = (clone $userTransfers)->count();
        $activeTransfers = (clone $userTransfers)
            ->where('expires_at', '>', now())
            ->count();
        $totalFiles = (int) (clone $userTransfers)->sum('file_count');
        $storageUsed = (int) (clone $userTransfers)->sum('total_size');

        return view('dashboard', [
            'transfers' => $transfers,
            'totalTransfers' => $totalTransfers,
            'activeTransfers' => $activeTransfers,
            'totalFiles' => $totalFiles,
            'storageUsed' => Number::fileSize($storageUsed),
            'period' => $period,
        ]);
    }
}
