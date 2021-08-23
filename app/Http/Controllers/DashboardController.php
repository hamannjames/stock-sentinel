<?php

namespace App\Http\Controllers;

use App\Models\Ticker;
use App\Models\Transactor;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $connectionsCount = $user->connections()->count();

        if (!$connectionsCount) {
            return view('dashboard', ['connectionsCount' => $connectionsCount]);
        }

        $connectedTransactors = Transactor::whereIn('id', $user->connections()
            ->whereHasMorph('connectable', Transactor::class)
            ->select('connectable_id')
            ->get()
            ->pluck('connectable_id')
            ->all()
        )->get();

        $connectedTickers = Ticker::whereIn('id', $user->connections()
            ->whereHasMorph('connectable', Ticker::class)
            ->get()
            ->pluck('connectable_id')
            ->all()
        )->get();

        $transactorTransactions = $connectedTransactors->isNotEmpty() ? Transaction::with('transactor')
            ->with('ticker')
            ->with('transactionType')
            ->whereIn('transactor_id', $connectedTransactors->pluck('id')->all())
            ->orderByDesc('transaction_date')
            ->get()
        : false;

        $tickerTransactions = $connectedTickers->isNotEmpty() ? Transaction::with('transactor')
            ->with('ticker')
            ->with('transactionType')
            ->whereIn('ticker_id', $connectedTickers->pluck('id')->all())
            ->orderByDesc('transaction_date')
            ->get()
        : false;

        $transactorStart = $transactorTransactions ? $transactorTransactions->last()->transaction_date : false;
        $transactorEnd = $transactorTransactions ? $transactorTransactions->first()->transaction_date : false;
        $tickerStart = $tickerTransactions ? $tickerTransactions->last()->transaction_date : false;
        $tickerEnd = $tickerTransactions ? $tickerTransactions->first()->transaction_date : false;

        return view('dashboard', [
            'connectionsCount' => $connectionsCount,
            'connectedTransactors' => $connectedTransactors,
            'connectedTickers' => $connectedTickers,
            'transactorTransactions' => $transactorTransactions,
            'tickerTransactions' => $tickerTransactions,
            'transactorStart' => $transactorStart,
            'transactorEnd' => $transactorEnd,
            'tickerStart' => $tickerStart,
            'tickerEnd' => $tickerEnd
        ]);
    }
}
