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
