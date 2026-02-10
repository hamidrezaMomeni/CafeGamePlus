<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTableSessionRequest;
use App\Models\Table;
use App\Models\TableSession;
use App\Services\PricingPlanService;
use App\Services\SessionEndService;
use App\Support\JalaliDate;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class TableSessionController extends Controller
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

    public function store(StoreTableSessionRequest $request): RedirectResponse
    {
        $table = Table::findOrFail($request->table_id);

        $startTime = $request->validated('start_time')
            ? JalaliDate::parse($request->validated('start_time'), true)
            : Carbon::now();

        $startTime = $startTime ?? Carbon::now();
        $plannedDurationMinutes = $request->validated('planned_duration_minutes');
        $bonusMinutes = $plannedDurationMinutes
            ? $this->pricingPlanService->bonusMinutesForPlannedDuration('table', $startTime, (int) $plannedDurationMinutes)
            : 0;

        $session = TableSession::create([
            'table_id' => $request->table_id,
            'customer_id' => $request->customer_id,
            'planned_duration_minutes' => $plannedDurationMinutes,
            'planned_end_time' => $plannedDurationMinutes ? $startTime->copy()->addMinutes((int) $plannedDurationMinutes + $bonusMinutes) : null,
            'discount_percent' => $request->validated('discount_percent'),
            'start_time' => $startTime,
            'status' => 'active',
        ]);

        $table->update(['status' => 'busy']);

        return redirect()->route('dashboard')->with('success', 'سشن میز با موفقیت شروع شد');
    }

    public function end(TableSession $tableSession): RedirectResponse
    {
        try {
            $invoice = $this->sessionEndService->endTableSession($tableSession);
        } catch (\RuntimeException $e) {
            return back()->with('error', 'این سشن فعال نیست');
        }

        return redirect()->route('dashboard')
            ->with('success', 'سشن به پایان رسید و فاکتور ثبت شد')
            ->with('open_invoice_id', $invoice->id);
    }
}
