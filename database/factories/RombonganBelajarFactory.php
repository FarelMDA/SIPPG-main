<?php

namespace Database\Factories;

use App\Models\Kelompok;
use App\Models\RombonganBelajar;
use Illuminate\Database\Eloquent\Factories\Factory;

class RombonganBelajarFactory extends Factory
{
    protected $model = RombonganBelajar::class;

    public function definition(): array
    {
        return [
            'kelompok_id' => Kelompok::factory(),
            'nama' => 'Kelas '.fake()->unique()->lexify('???'),
            'status_aktif' => true,
        ];
    }
}
