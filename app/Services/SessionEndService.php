<?php

namespace App\Services;

use App\Models\ConsoleSession;
use App\Models\TableSession;
use App\Models\BoardGameSession;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SessionEndService
{
    public function __construct(
        protected PricingService $pricingService,
        protected PricingPlanService $pricingPlanService,
        protected InvoiceService $invoiceService,
    ) {
    }

    public function endConsoleSession(ConsoleSession $consoleSession, ?Carbon $endTime = null, bool $autoClosed = false): Invoice
    {
        $endTime = $endTime ?? Carbon::now();

        if ($autoClosed && $consoleSession->planned_end_time) {
            $endTime = $consoleSession->planned_end_time;
        }

        if ($consoleSession->status !== 'active') {
            throw new RuntimeException('Session is not active.');
        }

        return DB::transaction(function () use ($consoleSession, $endTime) {
            $endTime = $endTime->copy();

            $duration = $consoleSession->start_time->diffInMinutes($endTime);
            $adjustments = $this->pricingPlanService->adjustmentsForSession(
                'console',
                $consoleSession->customer,
                $consoleSession->start_time,
                $endTime,
                $duration
            );

            $billableMinutes = max(0, $duration - (int) ($adjustments['bonus_minutes'] ?? 0));
            $billedMinutes = max(30, $billableMinutes);

            $price = $this->pricingService->calculateConsolePrice(
                $consoleSession->console,
                (int) $consoleSession->controller_count,
                $billedMinutes
            );

            $price = $this->applyDiscountPercent($price, (int) ($adjustments['discount_percent'] ?? 0));
            $price = $this->applyDiscountPercent($price, $consoleSession->discount_percent);

            $consoleSession->end_time = $endTime;
            $consoleSession->duration_minutes = $duration;
            $consoleSession->total_price = $price;
            $consoleSession->status = 'completed';
            $consoleSession->save();

            $consoleSession->console->update(['status' => 'available']);

            $invoice = $consoleSession->invoice;

            if (! $invoice) {
                $invoice = $this->invoiceService->createInvoice($consoleSession->customer, $price);
            } elseif (! $invoice->customer_id && $consoleSession->customer_id) {
                $invoice->customer_id = $consoleSession->customer_id;
                $invoice->save();
            }

            $consoleSession->invoice_id = $invoice->id;
            $consoleSession->save();

            $this->invoiceService->updateInvoiceTotal($invoice);

            if ($consoleSession->customer) {
                $consoleSession->customer->increment('total_spend', $price);
                $consoleSession->customer->increment('visit_count');
            }

            return $invoice;
        });
    }

    public function endTableSession(TableSession $tableSession, ?Carbon $endTime = null, bool $autoClosed = false): Invoice
    {
        $endTime = $endTime ?? Carbon::now();

        if ($autoClosed && $tableSession->planned_end_time) {
            $endTime = $tableSession->planned_end_time;
        }

        if ($tableSession->status !== 'active') {
            throw new RuntimeException('Session is not active.');
        }

        return DB::transaction(function () use ($tableSession, $endTime) {
            $endTime = $endTime->copy();

            $duration = $tableSession->start_time->diffInMinutes($endTime);
            $adjustments = $this->pricingPlanService->adjustmentsForSession(
                'table',
                $tableSession->customer,
                $tableSession->start_time,
                $endTime,
                $duration
            );

            $billableMinutes = max(0, $duration - (int) ($adjustments['bonus_minutes'] ?? 0));
            $billedMinutes = max(30, $billableMinutes);

            $price = $this->pricingService->calculateTablePrice(
                $tableSession->table,
                $billedMinutes
            );

            $price = $this->applyDiscountPercent($price, (int) ($adjustments['discount_percent'] ?? 0));
            $price = $this->applyDiscountPercent($price, $tableSession->discount_percent);

            $tableSession->end_time = $endTime;
            $tableSession->duration_minutes = $duration;
            $tableSession->total_price = $price;
            $tableSession->status = 'completed';
            $tableSession->save();

            $tableSession->table->update(['status' => 'available']);

            $invoice = $tableSession->invoice;

            if (! $invoice) {
                $invoice = $this->invoiceService->createInvoice($tableSession->customer, $price);
            } elseif (! $invoice->customer_id && $tableSession->customer_id) {
                $invoice->customer_id = $tableSession->customer_id;
                $invoice->save();
            }

            $tableSession->invoice_id = $invoice->id;
            $tableSession->save();

            $this->invoiceService->updateInvoiceTotal($invoice);

            if ($tableSession->customer) {
                $tableSession->customer->increment('total_spend', $price);
                $tableSession->customer->increment('visit_count');
            }

            return $invoice;
        });
    }

    public function endBoardGameSession(BoardGameSession $boardGameSession, ?Carbon $endTime = null, bool $autoClosed = false): Invoice
    {
        $endTime = $endTime ?? Carbon::now();

        if ($autoClosed && $boardGameSession->planned_end_time) {
            $endTime = $boardGameSession->planned_end_time;
        }

        if ($boardGameSession->status !== 'active') {
            throw new RuntimeException('Session is not active.');
        }

        return DB::transaction(function () use ($boardGameSession, $endTime) {
            $endTime = $endTime->copy();

            $duration = $boardGameSession->start_time->diffInMinutes($endTime);
            $adjustments = $this->pricingPlanService->adjustmentsForSession(
                'board_game',
                $boardGameSession->customer,
                $boardGameSession->start_time,
                $endTime,
                $duration
            );

            $billableMinutes = max(0, $duration - (int) ($adjustments['bonus_minutes'] ?? 0));
            $billedMinutes = max(30, $billableMinutes);

            $price = $this->pricingService->calculateBoardGamePrice(
                $boardGameSession->boardGame,
                $billedMinutes
            );

            $price = $this->applyDiscountPercent($price, (int) ($adjustments['discount_percent'] ?? 0));
            $price = $this->applyDiscountPercent($price, $boardGameSession->discount_percent);

            $boardGameSession->end_time = $endTime;
            $boardGameSession->duration_minutes = $duration;
            $boardGameSession->total_price = $price;
            $boardGameSession->status = 'completed';
            $boardGameSession->save();

            $boardGameSession->boardGame->update(['status' => 'available']);

            $invoice = $boardGameSession->invoice;

            if (! $invoice) {
                $invoice = $this->invoiceService->createInvoice($boardGameSession->customer, $price);
            } elseif (! $invoice->customer_id && $boardGameSession->customer_id) {
                $invoice->customer_id = $boardGameSession->customer_id;
                $invoice->save();
            }

            $boardGameSession->invoice_id = $invoice->id;
            $boardGameSession->save();

            $this->invoiceService->updateInvoiceTotal($invoice);

            if ($boardGameSession->customer) {
                $boardGameSession->customer->increment('total_spend', $price);
                $boardGameSession->customer->increment('visit_count');
            }

            return $invoice;
        });
    }

    protected function applyDiscountPercent(float $price, ?int $discountPercent): float
    {
        $discountPercent = $discountPercent ?? 0;
        $discountPercent = max(0, min(100, $discountPercent));

        if ($discountPercent === 0) {
            return $price;
        }

        return round($price * (1 - ($discountPercent / 100)), 2);
    }
}
