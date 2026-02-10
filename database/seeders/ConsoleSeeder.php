<?php

namespace Database\Seeders;

use App\Models\Console;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConsoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $consoles = [
            ['name' => 'PS5 - 1', 'type' => 'PS5', 'hourly_rate_single' => 50000, 'hourly_rate_double' => 70000, 'hourly_rate_triple' => 90000, 'hourly_rate_quadruple' => 120000],
            ['name' => 'PS5 - 2', 'type' => 'PS5', 'hourly_rate_single' => 50000, 'hourly_rate_double' => 70000, 'hourly_rate_triple' => 90000, 'hourly_rate_quadruple' => 120000],
            ['name' => 'PS5 - 3', 'type' => 'PS5', 'hourly_rate_single' => 50000, 'hourly_rate_double' => 70000, 'hourly_rate_triple' => 90000, 'hourly_rate_quadruple' => 120000],
            ['name' => 'PS5 - 4', 'type' => 'PS5', 'hourly_rate_single' => 50000, 'hourly_rate_double' => 70000, 'hourly_rate_triple' => 90000, 'hourly_rate_quadruple' => 120000],
            ['name' => 'PS5 - 5', 'type' => 'PS4', 'hourly_rate_single' => 50000, 'hourly_rate_double' => 70000, 'hourly_rate_triple' => 90000, 'hourly_rate_quadruple' => 120000],
            ['name' => 'PS5 - 6', 'type' => 'PS4', 'hourly_rate_single' => 50000, 'hourly_rate_double' => 70000, 'hourly_rate_triple' => 90000, 'hourly_rate_quadruple' => 120000],
            ['name' => 'PS4 - 1', 'type' => 'PS4', 'hourly_rate_single' => 35000, 'hourly_rate_double' => 50000, 'hourly_rate_triple' => 65000, 'hourly_rate_quadruple' => 95000],
            ['name' => 'PS4 - 2', 'type' => 'PS4', 'hourly_rate_single' => 35000, 'hourly_rate_double' => 50000, 'hourly_rate_triple' => 65000, 'hourly_rate_quadruple' => 95000],
        ];

        foreach ($consoles as $console) {
            Console::create($console);
        }
    }
}
