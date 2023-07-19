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
    $instance = new User();
    $users = $instance->paginate(15);
    $usersCountByStatus = $instance->select([
        DB::raw('CASE 
                    WHEN status = 1 THEN "admin"
                    WHEN status = 2 THEN "user"
                    WHEN status = 3 THEN "customer"
                END as status_name'),
        DB::raw('COUNT(*) as total')
    ])
        ->groupBy('status')
        ->get();

    $data = array_merge([
        'data' => collect($users)->toArray()
    ], ['additional_data' => collect($usersCountByStatus)->toArray()]);

    return response()->json((object)$data);
});
