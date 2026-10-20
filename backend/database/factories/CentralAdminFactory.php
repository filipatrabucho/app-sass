<?php

namespace Database\Factories;

use App\Central\Models\CentralAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<CentralAdmin>
 */
class CentralAdminFactory extends Factory
{
    protected $model = CentralAdmin::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}
