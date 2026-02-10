<?php

namespace App\Http\Controllers;

use App\Models\ConsoleSession;
use App\Models\TableSession;
use App\Models\BoardGameSession;
use App\Services\SessionEndService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function tick(SessionEndService $sessionEndService): JsonResponse
    {
        $now = Carbon::now();

        $endedConsoleSessions = 0;
        $endedTableSessions = 0;
        $endedBoardGameSessions = 0;
        $invoiceIds = [];

        $consoleSessions = ConsoleSession::query()
            ->where('status', 'active')
            ->whereNotNull('planned_end_time')
            ->where('planned_end_time', '<=', $now)
            ->get();

        foreach ($consoleSessions as $consoleSession) {
            try {
                $invoice = $sessionEndService->endConsoleSession($consoleSession, null, true);
                $endedConsoleSessions++;
                $invoiceIds[] = $invoice->id;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $tableSessions = TableSession::query()
            ->where('status', 'active')
            ->whereNotNull('planned_end_time')
            ->where('planned_end_time', '<=', $now)
            ->get();

        foreach ($tableSessions as $tableSession) {
            try {
                $invoice = $sessionEndService->endTableSession($tableSession, null, true);
                $endedTableSessions++;
                $invoiceIds[] = $invoice->id;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        $boardGameSessions = BoardGameSession::query()
            ->where('status', 'active')
            ->whereNotNull('planned_end_time')
            ->where('planned_end_time', '<=', $now)
            ->get();

        foreach ($boardGameSessions as $boardGameSession) {
            try {
                $invoice = $sessionEndService->endBoardGameSession($boardGameSession, null, true);
                $endedBoardGameSessions++;
                $invoiceIds[] = $invoice->id;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return response()->json([
            'ended_console_sessions' => $endedConsoleSessions,
            'ended_table_sessions' => $endedTableSessions,
            'ended_board_game_sessions' => $endedBoardGameSessions,
            'invoice_ids' => $invoiceIds,
        ]);
    }
}
