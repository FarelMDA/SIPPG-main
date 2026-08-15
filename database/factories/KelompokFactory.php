<?php

namespace Database\Factories;

use App\Models\Desa;
use App\Models\Kelompok;
use Illuminate\Database\Eloquent\Factories\Factory;

class KelompokFactory extends Factory
{
    protected $model = Kelompok::class;

    public function definition(): array
    {
        return [
            'desa_id' => Desa::factory(),
            'nama' => 'KBM '.$this->faker->streetName(),
            'alamat' => $this->faker->address(),
        ];
    }
}
