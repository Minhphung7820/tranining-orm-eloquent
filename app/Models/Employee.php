<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';

    protected $fillable = [
        'id',
        'name',
        'shift_id',
        'position_id',
        'department_id',
        'job_id'
    ];

    public function shift()
    {
        return $this->belongsTo(EmployeeShift::class, 'shift_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function job_title()
    {
        return $this->belongsTo(JobTitle::class, 'job_id');
    }
}
