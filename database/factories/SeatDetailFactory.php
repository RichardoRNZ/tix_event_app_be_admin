<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Seat;
use App\Models\SeatDetail;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeatDetail>
 */
class SeatDetailFactory extends Factory
{
    protected $model = SeatDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $row = $this->faker->numberBetween(1, 20);
        $col = $this->faker->numberBetween(1, 20);

        return [
            'uuid'     => (string) Str::uuid(),
            'event_id' => Event::factory(),
            'seat_id'  => Seat::factory(),
            'position' => "($row, $col)",
        ];
    }
}
