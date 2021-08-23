<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

// This model represents connections between users and either senators or tickers. It is polymorphic.
// That is why this model uses the "morph" functions
class Connection extends Model
{
    use HasFactory;

    protected $guarded = [];

    // connectable is the name used to query relations to other entities
    public function connectable()
    {
        return $this->morphTo();
    }

    // return the user that has this connection
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
