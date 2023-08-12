<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildrenTimekeeping extends Model
{
    use HasFactory;

    protected $table = 'children_timekeepings';
    protected $fillable = [
        'id',
        'timekeeping_id',
        'scan_date',
        'check_in',
        'check_out',
        'order'
    ];
}
