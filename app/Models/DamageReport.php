<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    protected $table = 'damagereports';
    protected $primaryKey = 'damage_id';   // IMPORTANT: your PK is damage_id
    public $timestamps = true;

    protected $fillable = [
        'serial_no',
        'observation',
        'borrower_name',
        'reported_by',
        'reported_at',
    ];

    public function item()
    {
        return $this->hasOne(Item::class, 'serial_no', 'serial_no');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }
}