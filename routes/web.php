<?php

use App\Models\User;
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

Route::get('/', function () {
    $instance = User::query();
    $users = $instance->paginate(50);
    $usersCountByStatus = User::query()->select([
        'users.status',
        DB::raw('CASE 
                    WHEN status = 1 THEN "admin"
                    WHEN status = 2 THEN "user"
                    WHEN status = 3 THEN "customer"
                    WHEN status = 4 THEN "customer"
                    WHEN status = 5 THEN "customer"
                    WHEN status = 6 THEN "customer"
                    WHEN status = 7 THEN "customer"
                    WHEN status = 8 THEN "customer"
                    WHEN status = 9 THEN "customer"
                    WHEN status = 10 THEN "customer"
                    WHEN status = 11 THEN "customer"
                    WHEN status = 12 THEN "customer"
                    WHEN status = 13 THEN "customer"
                    WHEN status = 14 THEN "customer"
                    WHEN status = 15 THEN "customer"
                END as status_text'),
        DB::raw('COUNT(*) as total')
    ])
        ->groupBy('status')
        ->get();

    $data = array_merge([
        'data' => collect($users)->toArray()
    ], ['additional_data' => collect($usersCountByStatus)->toArray()]);

    return response()->json((object)$data);
});
