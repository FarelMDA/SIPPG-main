<?php

namespace Database\Factories;

use App\Models\JenisMusyawaroh;
use Illuminate\Database\Eloquent\Factories\Factory;

class JenisMusyawarohFactory extends Factory
{
    protected $model = JenisMusyawaroh::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(2, true),
            'tingkat' => fake()->randomElement(['KELOMPOK', 'DESA', 'DAERAH']),
            'urutan' => 1,
            'perlu_jumlah_hadir' => false,
        ];
    }
}
