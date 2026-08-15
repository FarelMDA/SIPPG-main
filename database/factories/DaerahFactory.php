<?php

namespace Database\Factories;

use App\Models\Daerah;
use Illuminate\Database\Eloquent\Factories\Factory;

class DaerahFactory extends Factory
{
    protected $model = Daerah::class;

    public function definition(): array
    {
        return [
            'nama' => 'PPG Daerah '.$this->faker->city(),
        ];
    }
}
