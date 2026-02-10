<?php

namespace App\Services;

use App\Models\Console;
use App\Models\BoardGame;
use App\Models\Table;

class PricingService
{
    public function calculateConsolePrice(Console $console, int $controllerCount, int $minutes): float
    {
        $hours = $minutes / 60;
        $rate = match ($controllerCount)
        {
            1 => $console->hourly_rate_single,
            2 => $console->hourly_rate_double,
            3 => $console->hourly_rate_triple,
            4 => $console->hourly_rate_quadruple,
            default => $console->hourly_rate_single,
        };

        return round($rate * $hours, 2);
    }

    public function calculateTablePrice(Table $table, int $minutes): float
    {
        $hours = $minutes / 60;
        return round($table->hourly_rate * $hours, 2);
    }

    public function calculateBoardGamePrice(BoardGame $boardGame, int $minutes): float
    {
        $hours = $minutes / 60;
        return round($boardGame->hourly_rate * $hours, 2);
    }
}
