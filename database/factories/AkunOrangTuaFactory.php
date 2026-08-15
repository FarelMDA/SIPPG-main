<?php

namespace Database\Factories;

use App\Models\AkunOrangTua;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AkunOrangTuaFactory extends Factory
{
    protected $model = AkunOrangTua::class;

    public function definition(): array
    {
        return [
            'nomor_hp' => '08'.$this->faker->unique()->numerify('##########'),
            'password' => Hash::make('password'),
            'must_change_password' => false,
            'is_active' => true,
        ];
    }
}
