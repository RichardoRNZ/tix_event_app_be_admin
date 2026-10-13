<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Menggunakan locale Indonesia
        $venues = [];
        $now = now();
        
        for ($i = 1; $i <= 1000; $i++) {
            $venues[] = [
                'uuid' => (string) Str::uuid(),
                'name' => $faker->company . ' ' . $faker->randomElement(['Arena', 'Stadium', 'Hall', 'Convention Center', 'Theater']),
                'desc' => $faker->sentence(10),
                'location' => $faker->latitude(-11, 6) . ', ' . $faker->longitude(95, 141), // Koordinat batas geografis Indonesia
                'address' => $faker->address,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($venues, 500) as $chunk) {
            DB::table('venues')->insert($chunk);
        }
    }
}
