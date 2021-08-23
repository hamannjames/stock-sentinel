<?php

namespace App\Models;

use App\Models\Connection;
use App\Models\Transaction;
use App\Models\TransactorType;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// model for transactor (senator)
class Transactor extends Model
{
    use Sluggable;
    
    // quick and dirty map for displaying full party name
    const PARTY_MAP = [
        'D' => 'Democrat',
        'R' => 'Republican',
        'ID' => 'Independent Democrat'
    ];

    use HasFactory;

    public function transactorType()
    {
        return $this->belongsto(TransactorType::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function connections()
    {
        return $this->morphMany(Connection::class, 'connectable');
    }

    // This is a magic function. If the transactor is queried, laravel knows to use this function
    // to massage the party column data and also return full name
    public function getPartyAttribute($partySymbol) {
        return array_key_exists($partySymbol, self::PARTY_MAP) ? ['symbol' => $partySymbol, 'name' => self::PARTY_MAP[$partySymbol]] : ['symbol' => $partySymbol, 'name' => $partySymbol];
    }

    // similar to above, this uses a config file to return the full state name
    public function getStateAttribute($stateSymbol) {
        return [
            'symbol' => $stateSymbol,
            'name' => config("states.{$stateSymbol}")
        ];
    }

    // the slug is created using a combo of first, middle, and last name
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['first_name', 'middle_name', 'last_name']
            ]
        ];
    }

    // weird function to get random avatar from gravatar as placeholder image. only returns a string
    public function getRandomAvatar()
    {
        $emailHash = md5($this->last_name . $this->id);
        $avatarDefaults = ['mp', 'identicon', 'monsterid', 'retro', 'robohash'];
        $randomInteger = rand(0, count($avatarDefaults) - 1);
        
        return "https://www.gravatar.com/avatar/{$emailHash}?s=200&d={$avatarDefaults[$randomInteger]}";
    }

    // I got tired of concatenating individual name fields to get full name, so I created a method to do it
    public function fullName()
    {
        $middleName = $this->middle_name ? " {$this->middle_name} " : ' ';

        return "{$this->first_name}{$middleName}{$this->last_name}";
    }

    // similar to above, but returns abbreviated first name
    public function shortName()
    {
        $firstName = substr($this->first_name, 0, 1) . '.';
        return "{$firstName} {$this->last_name}";
    }

    // quick and dirty calc of all a senator has invested in stock market
    public function amountInvested($transactions = null, $tickerId = null)
    {
        if (!$transactions) {
            $transactionsFiltered = $this->transactions()
                ->with('ticker')
                ->with('transactionType')
                ->when($tickerId, 
                    fn($query) => where('ticker_id', $tickerId)
                        ->orWhere('ticker_received_id', $tickerId)
                )
                ->get();
        }
        else if ($tickerId) {
            $transactionsFiltered = $transactions->where('ticker_id', $tickerId);
        }
        else {
            $transactionsFiltered = collect($transactions);
        }

        return $transactionsFiltered->reduce(function($carry, $item, $key) use ($tickerId) {
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
