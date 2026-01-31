<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Record>
 */
class RecordFactory extends Factory
{
    protected $model = Record::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'artist_id' => Artist::factory(),
            'title' => fake()->sentence(3),
            'year' => fake()->numberBetween(1950, 2024),
            'format' => fake()->randomElement(['LP', 'CD', '7"', '12"', 'EP']),
        ];
    }

    /**
     * Create a record for a specific artist.
     */
    public function forArtist(Artist $artist): static
    {
        return $this->state(fn (array $attributes) => [
            'artist_id' => $artist->id,
        ]);
    }
}
