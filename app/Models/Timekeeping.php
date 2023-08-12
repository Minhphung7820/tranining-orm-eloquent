<?php

namespace App\Models;

use App\Constants\CommonConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Timekeeping extends Model
{
    use HasFactory;

    protected $timefillter = [
        'year' => null,
        'month' => null
    ];
    protected $table = 'timekeepings';

    protected $appends = ['data_overtime_timekeeping'];
    protected $fillable = [
        'id',
        'employee_id',
        'shift_id',
        'type',
        'timekeeping_date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function shift()
    {
        return $this->belongsTo(EmployeeShift::class, 'shift_id');
    }

    public function setTimeFilter($year, $month)
    {
        $this->timefillter = [
            'year' => $year,
            'month' => $month
        ];

        return $this;
    }
    public function getDataOvertimeTimekeepingAttribute()
    {
        $lableTypeShift = CommonConstants::TYPE_SHIFT;

        $getMasterOfEmployee = DB::table('timekeepings')
            ->where('employee_id', $this->employee_id)
            ->pluck('id')
            ->toArray();
        $groupedResult = [];

        foreach ($getMasterOfEmployee as $id) {
            $groupedResult[] = $id;
        }

        $getChildren = collect(ChildrenTimekeeping::whereIn('timekeeping_id', $groupedResult)->get())
            ->toArray();

        $configs = collect(EmployeeShift::with(['configOvertimes'])->find($this->shift_id)->configOvertimes)
            ->toArray();

        foreach ($configs as $order => $config) {
            $total = 0;
            if (isset($lableTypeShift[$config['type_shift']])) {
                foreach ($getChildren as $orderTimeChild => $children) {
                    if ($order === $orderTimeChild) {
                        $startTime = strtotime($children['check_in']);
                        $endTime = strtotime($children['check_out']);

                        $timeDiffInSeconds = $endTime - $startTime;

                        $totalHours = $timeDiffInSeconds / 3600;

                        $total = number_format($totalHours, 2);
                    }
                }
                $lableTypeShift[$config['type_shift']] =  (float)$total;
            }
        }
        array_walk($lableTypeShift, function (&$value, $key) {
            if (is_string($value)) {
                $value = 0;
            }
        });
        $lableTypeShift['total_overtime'] = (float)number_format(array_sum($lableTypeShift), 2);
        return $this->timefillter;
    }
}
