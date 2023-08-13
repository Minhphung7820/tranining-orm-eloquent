<?php

namespace Helpers;

use Carbon\Carbon;

class Overtime
{
    protected static $startWorkingTime = [];

    protected static $endWorkingTime = [];

    protected static $timeFluctuates = 15;

    protected static $timesScan = [];

    public static function make(array $timesScan, array $configsOvertime): self
    {

        static::$startWorkingTime =  array_column($configsOvertime, 'start_time');
        static::$endWorkingTime =  array_column($configsOvertime, 'end_time');
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
        $startTimeBefore = $this->getFluctuatingTime($shift[0], $timeFluctuates['come_early'], false);
        $startTimeAfter = $this->getFluctuatingTime($shift[0], $timeFluctuates['come_delay']);
        $endTimeBefore = $this->getFluctuatingTime($shift[1], $timeFluctuates['out_early'], false);
        $endTimeAfter = $this->getFluctuatingTime($shift[1], $timeFluctuates['out_delay']);
        foreach ($timesScan as $time) {
            if ($time >= $startTimeBefore && $time <= $startTimeAfter) {
                $arrayCheckIn[] = $time;
            }

            if ($time >= $endTimeBefore && $time <= $endTimeAfter) {
                $arrayCheckOut[] = $time;
            }
        }
        $result = [
            'check_in' => !empty($arrayCheckIn) ? min($arrayCheckIn) : 'Chưa check in',
            'check_out' => !empty($arrayCheckOut) ? max($arrayCheckOut)  :  'Chưa check out',
        ];

        return $result;
    }

    public function doCalculate(): array
    {
        $timesScan = static::$timesScan;
        $timeFluctuates = static::$timeFluctuates;

        $shifts = collect(static::$startWorkingTime)->zip(static::$endWorkingTime)->mapWithKeys(function ($shift, $index) use ($timesScan, $timeFluctuates) {

            $validEntries = $this->getValidEntries($shift, $timesScan, $timeFluctuates);

            $checkIn =  $validEntries['check_in'];
            $checkOut = $validEntries['check_out'];

            return [
                "time_config_" . ($index + 1) => [
                    "check_in" => $checkIn,
                    "check_out" => $checkOut
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
}
