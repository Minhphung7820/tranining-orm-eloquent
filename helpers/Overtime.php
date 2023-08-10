<?php

namespace Helpers;

use Carbon\Carbon;

class Overtime
{
    protected static $startWorkingTime = [];

    protected static $endWorkingTime = [];

    protected static $minutesFluctuates = 15;

    protected static $timesScan = [];

    public static function make(array $timesScan, array $configsOvertime): self
    {

        static::$startWorkingTime =  array_column($configsOvertime, 'start_time');
        static::$endWorkingTime =  array_column($configsOvertime, 'end_time');
        static::$timesScan = $timesScan;
        return new static();
    }

    public function withMinutesFluctuates($minutes): self
    {
        static::$minutesFluctuates = $minutes;
        return new static();
    }

    public function getValidEntries($shift, $timesScan, $minutesFluctuates): array
    {
        return collect($timesScan)->filter(function ($time) use ($shift, $timesScan, $minutesFluctuates) {
            $startTimeBefore = $this->getFluctuatingTime($shift[0], $minutesFluctuates, false);
            $startTimeAfter = $this->getFluctuatingTime($shift[0], $minutesFluctuates);
            $endTimeBefore = $this->getFluctuatingTime($shift[1], $minutesFluctuates, false);
            $endTimeAfter = $this->getFluctuatingTime($shift[1], $minutesFluctuates);

            $arrayInVariableCheckIn = array_filter($timesScan, function ($time) use ($startTimeBefore, $startTimeAfter) {
                return $time >= $startTimeBefore && $time <= $startTimeAfter;
            });

            $arrayInVariableCheckOut = array_filter($timesScan, function ($time) use ($endTimeBefore, $endTimeAfter) {
                return $time >= $endTimeBefore && $time <= $endTimeAfter;
            });

            if (empty($arrayInVariableCheckIn) || empty($arrayInVariableCheckOut)) {
                return null;
            }

            return ($time >= min($arrayInVariableCheckIn)) && ($time <= max($arrayInVariableCheckOut));
        })->toArray();
    }

    public function doCalculate(): array
    {
        $timesScan = static::$timesScan;
        $minutesFluctuates = static::$minutesFluctuates;

        $shifts = collect(static::$startWorkingTime)->zip(static::$endWorkingTime)->mapWithKeys(function ($shift, $index) use ($timesScan, $minutesFluctuates) {

            $validEntries = $this->getValidEntries($shift, $timesScan, $minutesFluctuates);

            $checkIn =  !empty($validEntries) ? min($validEntries) : null;
            $checkOut = !empty($validEntries) ? max($validEntries) : null;

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
