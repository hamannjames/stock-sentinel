<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function __invoke()
    {
        $endDate = Carbon::now()->subWeeks(2);
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
