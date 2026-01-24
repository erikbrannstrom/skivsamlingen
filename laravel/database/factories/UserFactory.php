<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = fake()->unique()->userName();

        return [
            'username' => $username,
            'password' => User::encryptPassword($username, 'password'),
            'email' => fake()->unique()->safeEmail(),
            'public_email' => fake()->boolean(),
            'name' => fake()->name(),
            'birth' => fake()->optional()->date('Y-m-d'),
            'about' => fake()->optional()->paragraph(),
            'sex' => fake()->randomElement(['f', 'm', 'x']),
            'per_page' => fake()->numberBetween(10, 100),
            'registered' => fake()->dateTimeBetween('-5 years', 'now'),
        ];
    }
}
