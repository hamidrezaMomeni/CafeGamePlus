<?php

namespace App\Http\Controllers;

use App\Models\CafeItem;
use App\Models\Invoice;
use App\Models\ConsoleSession;
use App\Models\TableSession;
use App\Models\BoardGameSession;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        $alreadyPaid = false;

        try {
            DB::transaction(function () use ($invoice, &$alreadyPaid) {
                $invoice = Invoice::query()
                    ->lockForUpdate()
                    ->findOrFail($invoice->id);

                if ($invoice->status === 'paid') {
                    $alreadyPaid = true;
                    return;
                }

                $requiredStocks = OrderItem::query()
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.invoice_id', $invoice->id)
                    ->groupBy('order_items.cafe_item_id')
                    ->selectRaw('order_items.cafe_item_id')
                    ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as required_quantity')
                    ->get();

                if ($requiredStocks->isNotEmpty()) {
                    $cafeItems = CafeItem::query()
                        ->whereIn('id', $requiredStocks->pluck('cafe_item_id'))
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    $insufficientItems = [];

                    foreach ($requiredStocks as $requiredStock) {
                        $itemId = (int) $requiredStock->cafe_item_id;
                        $needed = (int) $requiredStock->required_quantity;
                        $item = $cafeItems->get($itemId);
                        $available = (int) ($item?->stock_quantity ?? 0);

                        if (! $item || $available < $needed) {
                            $insufficientItems[] = sprintf(
                                '%s (موجودی: %s، نیاز: %s)',
                                $item?->name ?? 'آیتم حذف‌شده',
                                number_format($available),
                                number_format($needed)
                            );
                        }
                    }

                    if (! empty($insufficientItems)) {
                        throw ValidationException::withMessages([
                            'stock' => 'موجودی برای پرداخت این فاکتور کافی نیست: ' . implode(' | ', $insufficientItems),
                        ]);
                    }

                    foreach ($requiredStocks as $requiredStock) {
                        $item = $cafeItems->get((int) $requiredStock->cafe_item_id);
                        $item?->decrement('stock_quantity', (int) $requiredStock->required_quantity);
                    }
                }

                $invoice->update(['status' => 'paid']);

                Order::query()
                    ->where('invoice_id', $invoice->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'completed']);
            });
        } catch (ValidationException $exception) {
            $error = collect($exception->errors())->flatten()->first();

            return redirect()->route('dashboard')
                ->with('error', $error ?: 'خطا در بروزرسانی موجودی آیتم‌های کافه.');
        }

        if ($alreadyPaid) {
            return redirect()->route('dashboard')
                ->with('success', 'این فاکتور قبلاً پرداخت شده است.');
        }

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
