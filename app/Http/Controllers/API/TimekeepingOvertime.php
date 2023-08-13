<?php

namespace App\Http\Controllers\API;

use App\Constants\CommonConstants;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Timekeeping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimekeepingOvertime extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $year = (int)now()->year;
        $month = (int)now()->month;

        if (isset($request->year) && $request->year) {
            $year = (int) $request->year;
        }

        if (isset($request->month) && $request->month) {
            $month = (int) $request->month;
        }

        $timeKeepings = Timekeeping::withYearAndMonth($year, $month)
            ->with([
                'employee:id,name,shift_id,position_id,department_id,job_id',
                'employee.department:id,name',
                'employee.position:id,name',
                'employee.job_title:id,name',
                'shift:id,name_shift',
                'shift.configOvertimes:id,shift_id,form_shift,coefficient'
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

        if (isset($request->keyword) && $request->keyword) {
            $timeKeepings->whereHas('employee', function ($query) use ($request) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->keyword) . '%']);
            });
        }

        if (isset($request->type_overtime) && $request->type_overtime) {
            switch ($request->type_overtime) {
                case 'overtime_with_coefficient':
                    $timeKeepings->whereHas('shift.configOvertimes', function ($query) {
                        $query->where('overtime_configs.form_shift', 'calculate_salary');
                        $query->where('coefficient', '>', 1);
                    });
                    break;
                case 'uncompensated_overtime':
                    $timeKeepings->whereHas('shift.configOvertimes', function ($query) {
                        $query->where('overtime_configs.form_shift', 'calculate_salary');
                        $query->where('coefficient', 1);
                    });
                    break;
                case 'compensatory_time_off':
                    $timeKeepings->whereHas('shift.configOvertimes', function ($query) {
                        $query->where('overtime_configs.form_shift', 'off_compensate');
                    });
                    break;
            }
        }

        $data = collect($timeKeepings->paginate($request->limit))->toArray();
        $total_by_form_shift = [];
        foreach (CommonConstants::TYPE_OVERTIME as $label => $value) {
            $total_by_form_shift[] = [
                'type_name' => $label,
                'total_count' => $this->getCountEmployeeByTypeOvertime($label, $request)
            ];
        }
        $data['total_by_form_shift'] = $total_by_form_shift;
        return response()->json($data);
    }


    public function getCountEmployeeByTypeOvertime($type, $request)
    {
        $employee = Employee::with([
            'shift' => function ($query) use ($type, $request) {
                $query->whereHas('configOvertimes', function ($subQuery) use ($type, $request) {
                    switch ($type) {
                        case 'overtime_with_coefficient':
                            $subQuery->where('form_shift', 'calculate_salary')
                                ->where('coefficient', '>', 1);
                            break;
                        case 'uncompensated_overtime':
                            $subQuery->where('form_shift', 'calculate_salary')
                                ->where('coefficient', '=', 1);
                            break;
                        case 'compensatory_time_off':
                            $subQuery->where('form_shift', 'off_compensate');
                            break;
                    }
                });
            }
        ]);

        if (isset($request->department_id) && $request->department_id) {
            $employee->whereHas('department', function ($query) use ($request) {
                $query->where('departments.id', $request->department_id);
            });
        }

        if (isset($request->position_id) && $request->position_id) {
            $employee->whereHas('position', function ($query) use ($request) {
                $query->where('positions.id', $request->position_id);
            });
        }

        if (isset($request->job_title_id) && $request->job_title_id) {
            $employee->whereHas('job_title', function ($query) use ($request) {
                $query->where('job_titles.id', $request->job_title_id);
            });
        }

        if (isset($request->keyword) && $request->keyword) {
            $employee->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($request->keyword) . '%']);
        }
        $count = $employee->count();
        return $count;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
