<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chatbot extends Model
{
    use HasFactory;

    protected $table = 'chatbot'; // use your new table

    protected $primaryKey = 'chatbot_id';

    protected $fillable = [
        'category',
        'template_name',
        'input_label',
        'input_type',
        'sql_query',
    ];
}
