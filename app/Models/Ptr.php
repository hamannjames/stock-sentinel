<?php

namespace App\Models;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ptr extends Model
{
    use HasFactory;

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
