<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid'     => (string) Str::uuid(),
            'name'     => $this->faker->company() . ' ' . $this->faker->randomElement(['Hall', 'Arena', 'Convention Center', 'Stadium', 'Theater']),
            'desc'     => $this->faker->sentence(10),
            'location' => $this->faker->city(),
            'address'  => $this->faker->address(),
        ];
    }
}
