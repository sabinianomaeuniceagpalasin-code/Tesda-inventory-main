<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'item_id',
        'title',
        'message',
        'type',
    ];
}
