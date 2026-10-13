<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid'         => (string) Str::uuid(),
            'venue_id'     => Venue::factory(),
            'name'         => $this->faker->randomElement(['Music Festival', 'Tech Conference', 'Art Exhibition', 'Sports Championship', 'Dance Show']) . ' ' . $this->faker->year(),
            'event_date'   => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d H:i:s'),
            'desc'         => $this->faker->paragraph(2),
            'status'       => $this->faker->randomElement(['active', 'inactive', 'draft']),
            'code'         => strtoupper(Str::random(6)),
            'max_capacity' => $this->faker->numberBetween(50, 5000),
        ];
    }
}
