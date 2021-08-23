<?php

namespace App\Models;

use App\Models\Connection;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Model for tickers in database
class Ticker extends Model
{
    use HasFactory, Sluggable;

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function connections()
    {
        return $this->morphMany(Connection::class, 'connectable');
    }

    // This function specifically handles a blank ticker by creating a ticker record with the "--"
    // symbol in the DB. It also saves the name for debugging and cleaning later.
    /** @todo try to find matching ticker with name before creating double dash ticker */
    public static function handleBlankTicker($name)
    {
        $newTicker = new self();
        $blankCount = self::where('symbol', 'LIKE', '--%')->count();
        $newTicker->symbol = '--' . ($blankCount + 1);
        $newTicker->name = $name;
        $newTicker->save();
        
        return $newTicker;
    }

    // for sluggable method, use symbol
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'symbol'
            ]
        ];
    }

    // quick and dirty way of determinining how much money is invested in this stock
    public function amountInvested($transactions = null)
    {
        if (!$transactions) {
            $transactions = $this->transactions()
                ->with('transactionType')
                ->get();
        }

        // reduce to single value. if transaction is purchase, add to total. otherwise if sale, substract
        return $transactions->reduce(function($carry, $item, $key) {
            if ($item->transactionType->name === 'purchase') {
                $carry['min'] += $item->transaction_amount_min;
                $carry['max'] += $item->transaction_amount_max;
            }
            else if ($item->transactionType->name === 'sale (partial)' || $item->transactionType->name === 'sale (full)') {
                $carry['min'] -= $item->transaction_amount_min;
                $carry['max'] -= $item->transaction_amount_max;
            }

            return $carry;
        }, ['min' => 0, 'max' => 0]);
    }
}
