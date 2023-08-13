<?php

namespace Helpers;

use Carbon\Carbon;

class Overtime
{
    protected static $startWorkingTime = [];

    protected static $breakTime = [];

    protected static $endBreakTime = [];

    protected static $endWorkingTime = [];

    protected static $timeFluctuates = 15;

    protected static $timesScan = [];

    public static function make(array $timesScan, array $configsOvertime): self
    {

        static::$startWorkingTime =  array_column($configsOvertime, 'start_time');
        static::$endWorkingTime =  array_column($configsOvertime, 'end_time');
        static::$breakTime =  array_column($configsOvertime, 'break_time');
        static::$endBreakTime =  array_column($configsOvertime, 'end_break_time');
        static::$timesScan = $timesScan;
        return new static();
    }

    public function withMinutesFluctuates(array $timeFluctuates): self
    {
        static::$timeFluctuates = $timeFluctuates;
        return new static();
    }

    public function getValidEntries($shift, $timesScan, $timeFluctuates): array
    {
        $result = [];
        $arrayCheckIn = [];
        $arrayCheckOut = [];
        $status = 'null';
        $startTimeBefore = $this->getFluctuatingTime($shift[0], $timeFluctuates['come_early'], false);
        $startTimeAfter = $this->getFluctuatingTime($shift[0], $timeFluctuates['come_delay']);
        $endTimeBefore = $this->getFluctuatingTime($shift[1], $timeFluctuates['out_early'], false);
        $endTimeAfter = $this->getFluctuatingTime($shift[1], $timeFluctuates['out_delay']);
        foreach ($timesScan as $time) {
            if ($time >= $startTimeBefore && $time <= $startTimeAfter) {
                $arrayCheckIn['in_zone'][] = $time;
            } elseif ($time < $shift[2] && $time >= $startTimeAfter) {
                $arrayCheckIn['out_zone'][] = $time;
            }
            if ($time >= $endTimeBefore && $time <= $endTimeAfter) {
                $arrayCheckOut['in_zone'][] = $time;
            } elseif ($time >= $shift[3] && $time <= $endTimeBefore) {
                $arrayCheckOut['out_zone'][] = $time;
            }
        }

        $resultData = $this->findStatus($arrayCheckIn, $arrayCheckOut, $shift);
        // dd($status);
        $result = [
            'check_in' => $resultData['check_in'],
            'check_out' =>  $resultData['check_out'],
            'status' => $resultData['status'],
        ];

        return $result;
    }

    public function doCalculate(): array
    {
        $timesScan = static::$timesScan;
        $timeFluctuates = static::$timeFluctuates;
        $breakTime = static::$breakTime;
        $endBreakTime = static::$endBreakTime;


        $shifts = collect(static::$startWorkingTime)->zip(static::$endWorkingTime, $breakTime, $endBreakTime)->mapWithKeys(function ($shift, $index) use ($timesScan, $timeFluctuates) {

            $validEntries = $this->getValidEntries($shift, $timesScan, $timeFluctuates);
            $checkIn =  $validEntries['check_in'];
            $checkOut = $validEntries['check_out'];
            $status = $validEntries['status'];

            return [
                "time_config_" . ($index + 1) => [
                    "check_in" => $checkIn,
                    "check_out" => $checkOut,
                    "status" => $status
                ]
            ];
        });

        return $shifts->toArray();
    }

    public function getFluctuatingTime($time, $minutes, $flag = true): string
    {
        $parse = Carbon::createFromFormat("H:i:s", $time);
        if ($flag) {
            $parse->addMinutes($minutes);
        } else {
            $parse->subMinutes($minutes);
        }
        $parse->format("H:i:s");

        $result = $parse->toTimeString();
        return $result;
    }

    public function findStatus($checkIn, $checkOut, $shift)
    {
        $timeSystem = static::$timeFluctuates;

        if (isset($checkIn['in_zone'])) {
            $check_in = !empty($checkIn) > 0 ? min($checkIn['in_zone']) : null;
        } else {
            $check_in = !empty($checkIn['out_zone']) > 0 ? min($checkIn['out_zone']) : null;
        }

        if (isset($checkOut['in_zone'])) {
            $check_out = !empty($checkOut) > 0 ? max($checkOut['in_zone']) : null;
        } else {
            $check_out = !empty($checkOut['out_zone']) > 0 ? max($checkOut['out_zone']) : null;
        }

        $data = [
            'check_in' => $check_in,
            'check_out' => $check_out,
        ];

        if (empty($checkIn) && empty($checkOut)) {
            $data['status'] = 'vắng';
            return $data;
        }

        if ((isset($checkIn['out_zone']) && $checkIn['out_zone']) && empty($checkIn['in_zone'])) {
            $timeWork = $this->calculationTime($check_in, $shift[2]);
            $timeOff = $this->calculationTime($shift[0], $check_in);
            if ($timeWork >= $timeOff) {
                $data['status'] = 'đi trễ';
            } else {
                $data['status'] = 'Nghỉ sáng';
            }
            return $data;
        }

        if (empty($check_in) && $check_out) {
            $data['status'] = 'quên checkin';
            return $data;
        }
        // dd($checkOut);
        if ((isset($checkOut['out_zone']) && $checkOut['out_zone']) && empty($checkOut['in_zone'])) {
            $timeWork = $this->calculationTime($shift[3], $check_out);

            $timeOff = $this->calculationTime($check_out, $shift[1]);


            if ($timeWork >= $timeOff) {
                $data['status'] = 'về sớm';
            } else {
                $data['status'] = 'Nghỉ chiều';
            }
            return $data;
        }

        if (empty($check_out) && $check_in) {
            $data['status'] = 'quên checkout';
            return $data;
        }





        if ($check_in >= $shift[0]) {
            $data['status'] = 'đi trễ';
            return $data;
        }

        if ($check_out <= $shift[1]) {
            $data['status'] = 'về sớm';
            return $data;
        }

        if ($check_in <=  $shift[0] && $check_out >= $shift[1]) {
            $data['status'] = 'đi làm';
            return $data;
        }
    }

    public function calculationTime($timeFirst, $timeSecond)
    {
        $timeFirst = $this->timeToMinute($timeFirst);
        $timeSecond =  $this->timeToMinute($timeSecond);
        $timeEarlyDeparture = $this->minutesToHour($timeSecond - $timeFirst);
        return $timeEarlyDeparture;
    }

    public function timeToMinute($timeString)
    {
        $timeParts = explode(":", $timeString);
        $hours = (int) $timeParts[0];
        $minutes = (int) $timeParts[1];
        $seconds = (int) $timeParts[2];
        $totalMinutes = ($hours * 60) + $minutes  + ($seconds / 60);
        return $totalMinutes;
    }

    public function minutesToHour($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $formattedTime = sprintf("%02d:%02d:%02d", $hours, $remainingMinutes, 0);

        return $formattedTime;
    }
}
