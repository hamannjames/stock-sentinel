<?php

namespace App\Http\Controllers;

use App\Models\Transactor;
use Illuminate\Http\Request;

class SenatorController extends Controller
{
    public function index()
    {
        return view('senator.index');
    }

    public function show(Transactor $senator)
    {
        $connected = auth()->user() && auth()->user()
            ->connections()
            ->whereHasMorph('connectable', Transactor::class)
            ->where('connectable_id', $senator->id)
            ->exists();

        $transactions = $senator->transactions()
            ->with('ticker')
            ->with('transactionType')
            ->orderByDesc('transaction_date')
            ->get();

        return view('senator.show', [
            'senator' => $senator,
            'transactions' => $transactions,
            'connected' => $connected
        ]);
    }
}
