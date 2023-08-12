<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeShift extends Model
{
    use HasFactory;

    protected $table = 'shifts';

    protected $fillable = [
        'id',
        'name_shift',
    ];

    public function configOvertimes()
    {
        return $this->hasMany(ConfigOvertime::class, 'shift_id');
    }
}
