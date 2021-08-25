<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

// home controller with one invoke method which returns home page
class HomeController extends Controller
{
    public function __invoke()
    {
        // pull few weeks worth of transaction data starting with 2 weeks ago, since ptrs often
        // contain data that is a few weeks old, so the database is often a bit behind real time data
        $endDate = Carbon::now()->subWeeks(2)->subDays(3);
        $startDate = $endDate->copy()->subWeeks(3);

        $transactions = Transaction::with('transactor')
            ->with('ticker')
            ->with('transactionType')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        return view('home', [
            'transactions' => $transactions,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString()
        ]);
    }
}
