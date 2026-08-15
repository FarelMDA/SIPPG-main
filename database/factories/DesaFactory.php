<?php

namespace Database\Factories;

use App\Models\Daerah;
use App\Models\Desa;
use Illuminate\Database\Eloquent\Factories\Factory;

class DesaFactory extends Factory
{
    protected $model = Desa::class;

    public function definition(): array
    {
        return [
            'daerah_id' => Daerah::factory(),
            'nama' => 'Desa '.$this->faker->citySuffix(),
        ];
    }
}
