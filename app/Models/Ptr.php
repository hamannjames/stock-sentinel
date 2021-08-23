<?php

namespace App\Models;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// model that maps to ptrs in database
class Ptr extends Model
{
    use HasFactory;

    protected $guarded = [];

    // get all transactions associated with this model through the uuid column
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'ptr_id', 'uuid');
    }
}
