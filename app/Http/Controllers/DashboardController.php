<?php

namespace App\Http\Controllers;

use App\Models\Ticker;
use App\Models\Transactor;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

// controller for dashboard requests. Right now only handles the main page
class DashboardController extends Controller
{
    public function index()
    {
        // get the user and all their connections
        $user = auth()->user();
        $connectionsCount = $user->connections()->count();

        // id no connections we can return early
        if (!$connectionsCount) {
            return view('dashboard', ['connectionsCount' => $connectionsCount]);
        }

        // get all transactors connected if the connection exists
        $connectedTransactors = Transactor::whereIn('id', $user->connections()
            ->whereHasMorph('connectable', Transactor::class)
            ->select('connectable_id')
            ->get()
            ->pluck('connectable_id')
            ->all()
        )->get();

        // get all tickers connected if the connection exists
        $connectedTickers = Ticker::whereIn('id', $user->connections()
            ->whereHasMorph('connectable', Ticker::class)
            ->get()
            ->pluck('connectable_id')
            ->all()
        )->get();

        // get all transactions for connected senators
        $transactorTransactions = $connectedTransactors->isNotEmpty() ? Transaction::with('transactor')
            ->with('ticker')
            ->with('transactionType')
            ->whereIn('transactor_id', $connectedTransactors->pluck('id')->all())
            ->orderByDesc('transaction_date')
            ->get()
        : false;

        // get all transactions for connected tickers
        $tickerTransactions = $connectedTickers->isNotEmpty() ? Transaction::with('transactor')
            ->with('ticker')
            ->with('transactionType')
            ->whereIn('ticker_id', $connectedTickers->pluck('id')->all())
            ->orderByDesc('transaction_date')
            ->get()
        : false;

        // get start and end dates of transactions for senators and tickers to pass to timeline
        $transactorStart = $transactorTransactions ? $transactorTransactions->last()->transaction_date : false;
        $transactorEnd = $transactorTransactions ? $transactorTransactions->first()->transaction_date : false;
        $tickerStart = $tickerTransactions ? $tickerTransactions->last()->transaction_date : false;
        $tickerEnd = $tickerTransactions ? $tickerTransactions->first()->transaction_date : false;

        // return dashboard view with all the data in place
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
