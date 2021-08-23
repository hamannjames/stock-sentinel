<?php

namespace App\Models;

use App\Models\Connection;
use App\Models\Transaction;
use App\Models\TransactorType;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transactor extends Model
{
    use Sluggable;
    
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

    public function getPartyAttribute($partySymbol) {
        return array_key_exists($partySymbol, self::PARTY_MAP) ? ['symbol' => $partySymbol, 'name' => self::PARTY_MAP[$partySymbol]] : ['symbol' => $partySymbol, 'name' => $partySymbol];
    }

    public function getStateAttribute($stateSymbol) {
        return [
            'symbol' => $stateSymbol,
            'name' => config("states.{$stateSymbol}")
        ];
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => ['first_name', 'middle_name', 'last_name']
            ]
        ];
    }

    public function getRandomAvatar()
    {
        $emailHash = md5($this->last_name . $this->id);
        $avatarDefaults = ['mp', 'identicon', 'monsterid', 'retro', 'robohash'];
        $randomInteger = rand(0, count($avatarDefaults) - 1);
        
        return "https://www.gravatar.com/avatar/{$emailHash}?s=200&d={$avatarDefaults[$randomInteger]}";
    }

    public function fullName()
    {
        $middleName = $this->middle_name ? " {$this->middle_name} " : ' ';

        return "{$this->first_name}{$middleName}{$this->last_name}";
    }

    public function shortName()
    {
        $firstName = substr($this->first_name, 0, 1) . '.';
        return "{$firstName} {$this->last_name}";
    }

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
            else {
                $carry['min'] -= $item->transaction_amount_min;
                $carry['max'] -= $item->transaction_amount_max;
            }

            return $carry;
        }, ['min' => 0, 'max' => 0]);
    }
}
