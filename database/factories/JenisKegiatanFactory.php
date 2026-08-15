<?php

namespace Database\Factories;

use App\Models\JenisKegiatan;
use Illuminate\Database\Eloquent\Factories\Factory;

class JenisKegiatanFactory extends Factory
{
    protected $model = JenisKegiatan::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(2, true),
        ];
    }
}
