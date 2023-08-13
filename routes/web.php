<?php

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Helpers\Overtime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (Request $request) {
    // Order::factory()->count(100)->create();
    $statusLabels = [
        "1" => "Alice",
        "2" => "Bob",
        "3" => "Charlie",
        "4" => "David",
        "5" => "Eva",
        "6" => "Frank",
        "7" => "Grace",
        "8" => "Hannah",
        "9" => "Isaac",
        "10" => "Jack",
        "11" => "Katie",
        "12" => "Liam",
        "13" => "Mia",
        "14" => "Nathan",
        "15" => "Olivia",
    ];

    $user = new User();
    $users = $user->paginate($request->limit);

    $statusCounts = $user->get()->groupBy('status')->map(function ($group) {
        return $group->count();
    });


    $statusCounts = collect($statusLabels)->mapWithKeys(function ($label, $status) use ($statusCounts) {
        return [$status => $statusCounts->get($status, 0)];
    });

    $data = $statusCounts->mapWithKeys(function ($count, $status) use ($statusLabels) {
        return ['total_user_with_name_' . $statusLabels[$status] => $count];
    })->toArray();

    return response()->json([
        'data' => $users->toArray(),
        'total' => $data
    ]);
});

Route::get('/users-with-orders', function () {
    $usersWithTotal = User::with('orders')
        ->select([
            'users.id',
            'users.name',
            'users.email',
            DB::raw('(select sum(`orders`.`total`) from `orders` where `users`.`id` = `orders`.`user_id`) as `orders_total_price`'),
            DB::raw('(select count(*) from `orders` where `users`.`id` = `orders`.`user_id`) as `orders_count`'),
        ])
        ->groupBy(['users.id', 'users.name', 'users.email'])
        ->havingRaw('orders_count > 2')
        ->orderByDesc('orders_total_price') // Sắp xếp giảm dần theo orders_total_price
        ->limit(3) // Lấy 3 bản ghi đầu tiên
        ->get();

    return response()->json($usersWithTotal);
});

Route::get("/overtime", function () {
    // Dùng collection không dùng vòng lặp
    $array = [
        "08:10:00",
        "08:14:00",
        "08:15:59",
        "08:44:00",
        "08:46:00",
        "09:40:00",
        "09:44:00",
        "10:20:00",
        "10:43:00",
        "10:44:00",
        '13:31:00',
        // '13:34:00',
        "16:49:00",
        "17:50:00",
        "18:10:00",
        '18:14:00',
        '18:32:00',
        '18:40:00',
        '18:45:00',
        '18:47:30',
        '18:52:40',
        '18:59:00',
        '19:00:00',
        '20:20:00',
        '20:22:00',
        '20:58:36',
        '21:23:00'
    ];
    // Ca 1 : Từ 8h30  -> 10:30
    // Ca 2 : Từ 13:30 -> 17:00
    // Ca 3 : Từ 18:30 -> 21:00
    $configs = [
        [
            "start_time" => '08:30:00',
            "end_time" => '10:30:00',
        ],
        [
            "start_time" => '13:30:00',
            "end_time" => '17:00:00',
        ],
        [
            "start_time" => '18:30:00',
            "end_time" => '21:00:00',
        ],
    ];
    // $startWorkingTime = ['08:30:00', '13:30:00', '18:30:00'];
    // $endWorkingTime = ["10:30:00", '17:00:00', '21:00:00'];
    // $shifts = collect($startWorkingTime)->zip($endWorkingTime)->mapWithKeys(function ($shift, $index) use ($array) {

    //     $validEntries = collect($array)->filter(function ($time) use ($shift, $array) {
    //         $startTimeBefore = Carbon::createFromFormat("H:i:s", $shift[0])->subMinutes(15);
    //         $startTimeBefore->format('H:i:s');
    //         $startTimeBefore = $startTimeBefore->toTimeString();

    //         $startTimeAfter = Carbon::createFromFormat("H:i:s", $shift[0])->addMinutes(15);
    //         $startTimeAfter->format('H:i:s');
    //         $startTimeAfter = $startTimeAfter->toTimeString();

    //         $endTimeBefore = Carbon::createFromFormat("H:i:s", $shift[1])->subMinutes(15);
    //         $endTimeBefore->format('H:i:s');
    //         $endTimeBefore = $endTimeBefore->toTimeString();

    //         $endTimeAfter = Carbon::createFromFormat("H:i:s", $shift[1])->addMinutes(15);
    //         $endTimeAfter->format('H:i:s');
    //         $endTimeAfter = $endTimeAfter->toTimeString();

    //         $arrayInVariableCheckIn = array_filter($array, function ($time) use ($startTimeBefore, $startTimeAfter) {
    //             return $time >= $startTimeBefore && $time <= $startTimeAfter;
    //         });

    //         $arrayInVariableCheckOut = array_filter($array, function ($time) use ($endTimeBefore, $endTimeAfter) {
    //             return $time >= $endTimeBefore && $time <= $endTimeAfter;
    //         });

    //         if (empty($arrayInVariableCheckIn) || empty($arrayInVariableCheckOut)) {
    //             return null;
    //         }

    //         return ($time >= min($arrayInVariableCheckIn)) && ($time <= max($arrayInVariableCheckOut));
    //     });

    //     $checkIn = $validEntries->min() ?? "Đi trễ không check in";
    //     $checkOut = $validEntries->max() ?? "Không check out";

    //     return [
    //         "shift_" . ($index + 1) => [
    //             "check_in" => $checkIn,
    //             "check_out" => $checkOut
    //         ]
    //     ];
    // });

    $result = Overtime::make($array, $configs)
        ->withMinutesFluctuates([
            'come_early' => 15,
            'come_delay' => 15,
            'out_early' => 15,
            'out_delay' => 15,
        ])
        ->doCalculate();


    return response()->json($result);
});



Route::get("/time-sheet", function () {
    // Dùng collection không dùng vòng lặp
    $array = [
        "08:10:00",
        "08:14:00",
        "08:20:00",
        "09:40:00",
        "09:44:00",
        "10:20:00",
        "10:43:00",
        "11:10:00",
        '13:16:00',
        '13:34:00',
        '12:00:00',
        "16:49:00",
        // "17:15:00",
        // "18:10:00",
        '18:14:00',
        '18:32:00',
        '18:40:00',
        '18:45:00',
        '18:47:30',
        '18:52:40',
        '18:59:00',
        '19:00:00',
        '20:20:00',
        '20:22:00',
        // '20:58:36',
        '21:23:00'
    ];

    $configs = [
        [
            "start_time" => '08:30:00',
            "end_time" => '17:30:00',
            "break_time" => '12:00:00',
            "end_break_time" => '13:30:00',
        ],
        // [
        //     "start_time" => '13:30:00',
        //     "end_time" => '17:00:00',
        // ],

    ];

    $result = Overtime::make($array, $configs)
        ->withMinutesFluctuates([
            'come_early' => 15,
            'come_delay' => 15,
            'out_early' => 15,
            'out_delay' => 15,
        ])
        ->doCalculate();


    return response()->json($result);
});
