<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seats = [];
        $now = now();
        for ($i = 1; $i <= 1000; $i++) {
            $seats[] = [
                'uuid' => (string) Str::uuid(),
                'no' => (string) $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($seats, 500) as $chunk) {
            DB::table('seats')->insert($chunk);
        }
    }
}
