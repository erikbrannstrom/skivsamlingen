<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Artist>
 */
class ArtistFactory extends Factory
{
    protected $model = Artist::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
        ];
    }

    /**
     * Create an artist with "The " prefix.
     */
    public function withThePrefix(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'The ' . fake()->unique()->lastName(),
        ]);
    }
}
