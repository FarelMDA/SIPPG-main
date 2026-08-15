<?php

namespace Database\Factories;

use App\Models\Generus;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

class GenerusFactory extends Factory
{
    protected $model = Generus::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->name(),
            'tanggal_lahir' => $this->faker->date(),
            'jenis_kelamin' => $this->faker->randomElement(['LAKI', 'PEREMPUAN']),
            'kelas_id' => Kelas::factory(),
            'jenjang_id' => fn (array $attributes) => Kelas::find($attributes['kelas_id'])->jenjang_id,
            'nama_orang_tua' => $this->faker->name(),
            'nomor_hp_orang_tua' => '08'.$this->faker->numerify('##########'),
            'status_domisili' => 'SETEMPAT',
            'status_aktif' => true,
        ];
    }
}
