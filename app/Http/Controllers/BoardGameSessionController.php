<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoardGameSessionRequest;
use App\Models\BoardGame;
use App\Models\BoardGameSession;
use App\Services\PricingPlanService;
use App\Services\SessionEndService;
use App\Support\JalaliDate;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class BoardGameSessionController extends Controller
{
    protected SessionEndService $sessionEndService;
    protected PricingPlanService $pricingPlanService;

    public function __construct(SessionEndService $sessionEndService, PricingPlanService $pricingPlanService)
    {
        $this->sessionEndService = $sessionEndService;
        $this->pricingPlanService = $pricingPlanService;
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreBoardGameSessionRequest $request): RedirectResponse
    {
        $boardGame = BoardGame::findOrFail($request->board_game_id);

        $startTime = $request->validated('start_time')
            ? JalaliDate::parse($request->validated('start_time'), true)
            : Carbon::now();

        $startTime = $startTime ?? Carbon::now();
        $plannedDurationMinutes = $request->validated('planned_duration_minutes');
        $bonusMinutes = $plannedDurationMinutes
            ? $this->pricingPlanService->bonusMinutesForPlannedDuration('board_game', $startTime, (int) $plannedDurationMinutes)
            : 0;

        BoardGameSession::create([
            'board_game_id' => $request->board_game_id,
            'customer_id' => $request->customer_id,
            'planned_duration_minutes' => $plannedDurationMinutes,
            'planned_end_time' => $plannedDurationMinutes ? $startTime->copy()->addMinutes((int) $plannedDurationMinutes + $bonusMinutes) : null,
            'discount_percent' => $request->validated('discount_percent'),
            'start_time' => $startTime,
            'status' => 'active',
        ]);

        $boardGame->update(['status' => 'busy']);

        return redirect()->route('dashboard')->with('success', 'سشن بردگیم با موفقیت شروع شد');
    }

    public function end(BoardGameSession $boardGameSession): RedirectResponse
    {
        try {
            $invoice = $this->sessionEndService->endBoardGameSession($boardGameSession);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'این سشن فعال نیست');
        }

        return redirect()->route('dashboard')
            ->with('success', 'سشن به پایان رسید و فاکتور ثبت شد')
            ->with('open_invoice_id', $invoice->id);
    }
}
