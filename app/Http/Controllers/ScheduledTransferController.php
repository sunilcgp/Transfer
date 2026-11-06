<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduledTransferController extends Controller
{
    public function index(Request $request): View
    {
        $transfers = Transfer::query()
            ->ownedBy($request->user())
            ->whereNotNull('scheduled_at')
            ->latest('scheduled_at')
            ->paginate(10);

        return view('scheduled.index', compact('transfers'));
    }
}
