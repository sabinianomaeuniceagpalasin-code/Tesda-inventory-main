<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';

    protected $primaryKey = 'item_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'item_name',
        'classification',
        'source_of_fund',
        'date_acquired',
        'property_no',
        'serial_no',
        'stock',
        'usage_count',
        'remarks',
        'status',
        'last_maintenance_date',
        'maintenance_interval_days',
        'maintenance_threshold_usage',
        'expected_life_hours',
        'total_usage_hours',
    ];

    protected $dates = [
        'date_acquired',
        'last_maintenance_date',
        'created_at',
        'updated_at',
    ];
}
