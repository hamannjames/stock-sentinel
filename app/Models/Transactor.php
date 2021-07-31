<?php

namespace App\Models;

use App\Models\Connection;
use App\Models\Transaction;
use App\Models\TransactorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transactor extends Model
{
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
}
