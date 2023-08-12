<?php

namespace App\Models;

use App\Constants\CommonConstants;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Timekeeping extends Model
{
    use HasFactory;

    protected static $timefillter = [
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

    public static function getSummaryOvertimeTimekeeping($request)
    {

        if (isset($request->year) && $request->year) {
            static::$timefillter['year']  = (int)$request->year;
        } else {
            static::$timefillter['year']  =  (int)now()->year;
        }

        if (isset($request->month) && $request->month) {
            static::$timefillter['month']  = (int)$request->month;
        } else {
            static::$timefillter['month']  =  (int)now()->month;
        }

        $timeKeepings = self::query()
            ->with([
                'employee:id,name,shift_id,position_id,department_id,job_id',
                'employee.department:id,name',
                'employee.position:id,name',
                'employee.job_title:id,name',
                'shift:id,name_shift'
            ])->select([
                'employee_id',
                'shift_id',
                'type',
            ])->groupBy([
                'employee_id',
                'shift_id',
                'type',
            ])->where('type', 'overtime');

        if (isset($request->department_id) && $request->department_id) {
            $timeKeepings->whereHas('employee.department', function ($query) use ($request) {
                $query->where('departments.id', $request->department_id);
            });
        }

        if (isset($request->position_id) && $request->position_id) {
            $timeKeepings->whereHas('employee.position', function ($query) use ($request) {
                $query->where('positions.id', $request->position_id);
            });
        }

        if (isset($request->job_title_id) && $request->job_title_id) {
            $timeKeepings->whereHas('employee.job_title', function ($query) use ($request) {
                $query->where('job_titles.id', $request->job_title_id);
            });
        }

        $data = $timeKeepings->paginate($request->limit);

        return $data;
    }

    public function getDataOvertimeTimekeepingAttribute()
    {
        $lableTypeShift = CommonConstants::TYPE_SHIFT;

        $getMasterOfEmployee = DB::table('timekeepings')
            ->where('employee_id', $this->employee_id)
            ->where('type', 'overtime')
            ->whereMonth('timekeeping_date', static::$timefillter['month'])
            ->whereYear('timekeeping_date', static::$timefillter['year'])
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
                $times = [];
                foreach ($getChildren as  $children) {
                    if (($order + 1) === $children['order']) {

                        $startTime = strtotime($children['check_in']);

                        $endTime = strtotime($children['check_out']);
                        $timeDiffInSeconds = $endTime - $startTime;

                        $totalHours = $timeDiffInSeconds / 3600;

                        $totalFormat = number_format($totalHours, 2);

                        $times[] = (float)$totalFormat;
                    }
                }
                $total = (float)number_format(array_sum($times), 2);
            }
            $lableTypeShift[$config['type_shift']] = $total;
        }
        array_walk($lableTypeShift, function (&$value, $key) {
            if (is_string($value)) {
                $value = 0;
            }
        });
        $lableTypeShift['total_overtime'] = (float)number_format(array_sum($lableTypeShift), 2);
        return $lableTypeShift;
    }
}
