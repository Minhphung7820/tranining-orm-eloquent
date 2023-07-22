<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $ids = User::pluck('id');

        // Lấy kết quả dưới dạng mảng
        $idsArray = collect($ids->all())->toArray();
        return [
            'user_id' => Arr::random($idsArray),
            'total' => $this->randomEvenPrice(),
        ];
    }

    public function randomEvenPrice()
    {
        $min = 100000;
        $max = 10000000;

        // Tạo số ngẫu nhiên trong khoảng từ $min đến $max
        $randomPrice = rand($min, $max);

        // Làm tròn số xuống thành số chẵn
        $evenPrice = floor($randomPrice / 100) * 100;

        // Đảm bảo số chẵn nhỏ nhất là $min
        if ($evenPrice < $min) {
            $evenPrice = $min;
        }

        return $evenPrice;
    }
}
