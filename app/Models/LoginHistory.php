<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    protected $table = 'login_histories'; // IMPORTANT

    protected $fillable = [
        'user_id',
        'logged_in_at',
        'logged_out_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];
}