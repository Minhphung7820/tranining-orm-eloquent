<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => random_int(1, 100),
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
