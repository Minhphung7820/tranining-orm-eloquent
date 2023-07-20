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

    $users = $user->paginate(15);

    $data = $user->get()->groupBy('status')->map(function ($group) {
        return $group->count();
    })->mapWithKeys(function ($count, $status) use ($statusLabels) {
        return ['total_user_with_name_' . $statusLabels[$status] => $count];
    })->toArray();

    return response()->json(array_merge([
        'data' => collect($users)->toArray()
    ], ['total' => $data]));
});
