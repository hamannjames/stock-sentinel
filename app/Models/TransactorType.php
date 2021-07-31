<?php

namespace App\Models;

use App\Models\Transactor;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactorType extends Model
{
    use HasFactory;

    public function transactors()
    {
        return $this->hasMany(Transactor::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
