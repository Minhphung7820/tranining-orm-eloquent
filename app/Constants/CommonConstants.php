<?php

namespace App\Constants;

final class CommonConstants
{
    public const TYPE_SHIFT = [
        'weekday' => 'Ngày thường',
        'weekday_night' => 'Ngày thường (đêm)',
        'off' => 'Ngày nghĩ',
        'off_night' => 'Ngày nghĩ (đêm)',
        'holiday' => 'Ngày lễ',
        'holiday_night' => 'Ngày lễ (đêm)'
    ];

    public const TYPE_OVERTIME = [
        "overtime_with_coefficient" => "Tăng ca có hệ số",
        "uncompensated_overtime" => "Tăng ca không có hệ số",
        "compensatory_time_off" => "Tăng ca nghĩ bù"
    ];
}
