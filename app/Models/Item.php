<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;
    protected $table = 'items';

    protected $primaryKey = 'item_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'item_name',
        'description',
        'classification',
        'source_of_fund',
        'date_acquired',
        'property_no',
        'serial_no',
        'stock',
        'status',
        'remarks',
        'maintenance_interval_days',
        'maintenance_threshold_usage',
        'expected_life_hours',
        'department',
        'specification',
        'unit_cost',
        'expected_life_years',
        'usage_count',
    ];

    protected $dates = [
        'date_acquired',
        'last_maintenance_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function damageReports()
    {
        return $this->hasMany(DamageReport::class, 'serial_no', 'serial_no');
    }
}
