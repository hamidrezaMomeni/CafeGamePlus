<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            ['name' => 'بیلیارد 1', 'type' => 'billiard', 'hourly_rate' => 80000],
            ['name' => 'بیلیارد 2', 'type' => 'billiard', 'hourly_rate' => 80000],
            ['name' => 'بیلیارد 3', 'type' => 'billiard', 'hourly_rate' => 80000],
            ['name' => 'اسنوکر 1', 'type' => 'snooker', 'hourly_rate' => 100000],
        ];

        foreach ($tables as $table) {
            Table::create($table);
        }
    }
}
