<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigOvertime extends Model
{
    use HasFactory;

    protected $table = 'overtime_configs';

    protected $fillable = [
        'id',
        'shift_id',
        'start_time',
        'end_time'
    ];
}
