<?php

use App\Models\Order;
use App\Models\User;
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
    $array = [
        "08:10:00",
        "08:35:00",
        "08:40:00",
        "09:40:00",
        "09:44:00",
        '13:15:00',
        '13:34:00',
        "16:49:00",
        "17:50:00",
        "18:10:00",
        '18:15:00',
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
    $startWorkingTime = ['08:30:00', '13:30:00', '18:30:00'];
    $endWorkingTime = ["10:30:00", '17:00:00', '21:00:00'];

    $shifts = collect($startWorkingTime)->zip($endWorkingTime)->mapWithKeys(function ($shift, $index) use ($array) {
        $validEntries = collect($array)->filter(function ($time) use ($shift) {
            return $time >= $shift[0] && $time <= $shift[1];
        });

        $checkIn = $validEntries->min();
        $checkOut = $validEntries->max();

        return [
            "shift_" . ($index + 1) => [
                "check_in" => $checkIn,
                "check_out" => $checkOut
            ]
        ];
    });

    $result = $shifts->toArray();
    return response()->json($result);
});
