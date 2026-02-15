<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    protected $table = 'damagereports';

    protected $fillable = [
        'serial_no',
        'reported_at',
    ];

    public $timestamps = true;

    public function item()
    {
        return $this->belongsTo(Item::class, 'serial_no', 'serial_no');
    }

}
