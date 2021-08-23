<?php

namespace App\Models;

use App\Models\Connection;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public static function handleBlankTicker($name)
    {
        $newTicker = new self();
        $blankCount = self::where('symbol', 'LIKE', '--%')->count();
        $newTicker->symbol = '--' . ($blankCount + 1);
        $newTicker->name = $name;
        $newTicker->save();
        
        return $newTicker;
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'symbol'
            ]
        ];
    }

    public function amountInvested($transactions = null)
    {
        if (!$transactions) {
            $transactions = $this->transactions()
                ->with('transactionType')
                ->get();
        }

        return $transactions->reduce(function($carry, $item, $key) {
            if ($item->transactionType->name === 'purchase') {
                $carry['min'] += $item->transaction_amount_min;
                $carry['max'] += $item->transaction_amount_max;
            }
            else {
                $carry['min'] -= $item->transaction_amount_min;
                $carry['max'] -= $item->transaction_amount_max;
            }

            return $carry;
        }, ['min' => 0, 'max' => 0]);
    }
}
