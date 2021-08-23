<?php

namespace App\Models;

use App\Models\Ptr;
use App\Models\Ticker;
use App\Models\Connection;
use App\Models\Transactor;
use App\Models\TransactorType;
use App\Models\TransactionType;
use App\Models\TransactionAssetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    public function transactor()
    {
        return $this->belongsTo(Transactor::class);
    }

    public function transactorType()
    {
        return $this->belongsTo(TransactorType::class);
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class);
    }

    public function transactionAssetType()
    {
        return $this->belongsTo(TransactionAssetType::class);
    }

    public function ticker()
    {
        return $this->belongsTo(Ticker::class);
    }

    public function ptr()
    {
        return $this->belongsTo(Ptr::class);
    }
}
