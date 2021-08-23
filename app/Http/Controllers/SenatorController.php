<?php

namespace App\Http\Controllers;

use App\Models\Transactor;
use Illuminate\Http\Request;

// class to control senator requests
class SenatorController extends Controller
{
    // for the index page, simply return the view
    public function index()
    {
        return view('senator.index');
    }

    // for the individual senator page, we need more data
    public function show(Transactor $senator)
    {
        // determine whether a user is connected to this senator
        $connected = auth()->user() && auth()->user()
            ->connections()
            ->whereHasMorph('connectable', Transactor::class)
            ->where('connectable_id', $senator->id)
            ->exists();

        // get all transactions made by this senator.
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
