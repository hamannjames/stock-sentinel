<?php

namespace App\Models;

use App\Models\Connection;
use App\Models\Transactor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use LaratrustUserTrait;
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function connections()
    {
        return $this->hasMany(Connection::class);
    }

    public function addConnection(Model $model)
    {
        Connection::firstOrCreate([
            'user_id' => $this->id,
            'connectable_id' => $model->id,
            'connectable_type' => get_class($model)
        ]);
    }

    public function removeConnection(Model $model)
    {
        $this->connections()
            ->whereHasMorph('connectable', get_class($model))
            ->where('connectable_id', $model->id)
            ->delete();
    }
}
