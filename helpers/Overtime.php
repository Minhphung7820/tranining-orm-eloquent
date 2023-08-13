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
                $arrayCheckIn[] = $time;
            } elseif ($time < $shift[3] && $time >= $startTimeAfter) {
                $arrayCheckIn['time'][] = $time;
            }

            if ($time >= $endTimeBefore && $time <= $endTimeAfter) {
                $arrayCheckOut[] = $time;
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

        if (isset($checkIn['time'])) {
            $check_in = count($checkIn['time']) > 0 ? min($checkIn['time']) : null;
        } else {
            $check_in = count($checkIn) > 0 ? min($checkIn) : null;
        }

        $check_out = count($checkOut) > 0 ? max($checkOut)  :  null;

        $data = [
            'check_in' => $check_in,
            'check_out' => $check_out,
        ];
        $status = null;

        if (empty($checkIn) || empty($checkOut)) {
            $data['status'] = 'vắng';
            return $data;
        }

        if (empty($check_in) && $check_out) {
            $data['status'] = 'quên checkin';
            return $data;
        }

        if (empty($check_out) && $check_in) {
            $data['status'] = 'quên checkout';
            return $data;
        }

        if (isset($checkIn['time']) && $checkIn['time']) {
            $data['status'] = 'Nghỉ sáng';
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

    public function minutesToHour($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $formattedTime = sprintf("%02d:%02d:%02d", $hours, $remainingMinutes, 0);

        return $formattedTime;
    }
}
