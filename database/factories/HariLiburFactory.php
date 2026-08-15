<?php

namespace Database\Factories;

use App\Models\HariLibur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HariLiburFactory extends Factory
{
    protected $model = HariLibur::class;

    public function definition(): array
    {
        return [
            'nama' => 'Libur '.$this->faker->word(),
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(10)->toDateString(),
            'sumber' => 'MANUAL',
            'google_event_id' => null,
            'disunting_manual' => false,
            'dibuat_oleh' => User::factory(),
        ];
    }
}
