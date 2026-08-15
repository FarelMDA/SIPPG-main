<?php

namespace Database\Factories;

use App\Models\Daerah;
use App\Models\Desa;
use App\Models\Kelompok;
use App\Models\LaporanBulanan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LaporanBulananFactory extends Factory
{
    protected $model = LaporanBulanan::class;

    public function definition(): array
    {
        $kelompok = Kelompok::factory();

        return [
            'kelompok_id' => $kelompok,
            'desa_id' => null,
            'daerah_id' => null,
            'tingkat' => 'KELOMPOK',
            'periode' => now()->format('Y-m'),
            'versi' => 1,
            'status' => 'DRAFT',
            'snapshot_data' => null,
            'dibuat_oleh' => User::factory(),
        ];
    }

    public function final(): static
    {
        return $this->state(fn () => [
            'status' => 'FINAL',
            'snapshot_data' => ['cover' => ['kelompok' => 'Contoh', 'periode' => now()->format('Y-m')]],
            'difinalisasi_oleh' => User::factory(),
            'difinalisasi_pada' => now(),
        ]);
    }

    public function desa(): static
    {
        return $this->state(fn () => [
            'kelompok_id' => null,
            'desa_id' => Desa::factory(),
            'tingkat' => 'DESA',
        ]);
    }

    public function daerah(): static
    {
        return $this->state(fn () => [
            'kelompok_id' => null,
            'daerah_id' => Daerah::factory(),
            'tingkat' => 'DAERAH',
        ]);
    }
}
