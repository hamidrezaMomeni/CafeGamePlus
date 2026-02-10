<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ConsoleSession;
use App\Models\TableSession;
use App\Models\BoardGameSession;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;

class InvoiceController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function show(Invoice $invoice): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function items(Invoice $invoice): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function markAsPaid(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['status' => 'paid']);

        return redirect()->route('dashboard')
            ->with('success', 'فاکتور به عنوان پرداخت شده علامت گذاری شد');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('dashboard')
            ->with('success', 'فاکتور با موفقیت حذف شد.');
    }

    protected function repairInvoiceLinksIfMissing(Invoice $invoice): void
    {
        $hasAny =
            $invoice->consoleSessions()->exists()
            || $invoice->tableSessions()->exists()
            || $invoice->boardGameSessions()->exists()
            || $invoice->orders()->exists();

        if ($hasAny) {
            return;
        }

        $start = $invoice->created_at->copy()->subMinutes(10);
        $end = $invoice->created_at->copy()->addMinutes(10);

        $customerId = $invoice->customer_id;

        $candidates = [];

        $consoleSessions = ConsoleSession::query()
            ->whereNull('invoice_id')
            ->where('status', 'completed')
            ->whereBetween('end_time', [$start, $end])
            ->where('total_price', $invoice->total_amount)
            ->when(
                $customerId,
                fn ($q) => $q->where('customer_id', $customerId),
                fn ($q) => $q->whereNull('customer_id')
            )
            ->get();

        foreach ($consoleSessions as $session) {
            if (! $session->end_time) {
                continue;
            }

            $candidates[] = [
                'type' => 'console',
                'model' => $session,
                'diff' => abs($session->end_time->getTimestamp() - $invoice->created_at->getTimestamp()),
            ];
        }

        $tableSessions = TableSession::query()
            ->whereNull('invoice_id')
            ->where('status', 'completed')
            ->whereBetween('end_time', [$start, $end])
            ->where('total_price', $invoice->total_amount)
            ->when(
                $customerId,
                fn ($q) => $q->where('customer_id', $customerId),
                fn ($q) => $q->whereNull('customer_id')
            )
            ->get();

        foreach ($tableSessions as $session) {
            if (! $session->end_time) {
                continue;
            }

            $candidates[] = [
                'type' => 'table',
                'model' => $session,
                'diff' => abs($session->end_time->getTimestamp() - $invoice->created_at->getTimestamp()),
            ];
        }

        $boardGameSessions = BoardGameSession::query()
            ->whereNull('invoice_id')
            ->where('status', 'completed')
            ->whereBetween('end_time', [$start, $end])
            ->where('total_price', $invoice->total_amount)
            ->when(
                $customerId,
                fn ($q) => $q->where('customer_id', $customerId),
                fn ($q) => $q->whereNull('customer_id')
            )
            ->get();

        foreach ($boardGameSessions as $session) {
            if (! $session->end_time) {
                continue;
            }

            $candidates[] = [
                'type' => 'board_game',
                'model' => $session,
                'diff' => abs($session->end_time->getTimestamp() - $invoice->created_at->getTimestamp()),
            ];
        }

        $orders = Order::query()
            ->whereNull('invoice_id')
            ->whereBetween('created_at', [$start, $end])
            ->where('total_price', $invoice->total_amount)
            ->when(
                $customerId,
                fn ($q) => $q->where('customer_id', $customerId),
                fn ($q) => $q->whereNull('customer_id')
            )
            ->get();

        foreach ($orders as $order) {
            $candidates[] = [
                'type' => 'order',
                'model' => $order,
                'diff' => abs($order->created_at->getTimestamp() - $invoice->created_at->getTimestamp()),
            ];
        }

        if (count($candidates) === 0) {
            return;
        }

        usort($candidates, function (array $a, array $b) {
            if ($a['diff'] === $b['diff']) {
                return $a['model']->id <=> $b['model']->id;
            }

            return $a['diff'] <=> $b['diff'];
        });

        $best = $candidates[0];
        $model = $best['model'];
        $model->invoice_id = $invoice->id;
        $model->save();
    }
}
