<?php

namespace Database\Factories;

use App\Models\Kelompok;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'nama' => $this->faker->name(),
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'),
            'kelompok_id' => Kelompok::factory(),
            'must_change_password' => false,
            'is_active' => true,
        ];
    }

    public function adminDaerah(): static
    {
        return $this->state(['kelompok_id' => null, 'desa_id' => null]);
    }

    public function unverified(): static
    {
        return $this->state(['must_change_password' => true]);
    }
}
