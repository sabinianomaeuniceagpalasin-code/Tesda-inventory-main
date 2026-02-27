<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    protected $table = 'damagereports';
    protected $primaryKey = 'damage_id'; // ✅ because your PK is damage_id
    public $incrementing = true;

    protected $fillable = [
        'serial_no',
        'reported_at',
        'borrower_name',
        'observation', // ✅ we will add this column
        'reported_by', // ✅ optional: who reported it
    ];

    public $timestamps = true;

    public function item()
    {
        return $this->belongsTo(Item::class, 'serial_no', 'serial_no');
    }
}