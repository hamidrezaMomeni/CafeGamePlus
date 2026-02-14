<?php

namespace App\Http\Controllers;

use App\Models\BoardGameSession;
use App\Models\ConsoleSession;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TableSession;
use App\Support\JalaliDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AccountingStatsController extends Controller
{
    public function index(Request $request): View
    {
        $rangeOptions = $this->rangeOptions();
        $selectedRange = (string) $request->query('range', '30d');

        if (! array_key_exists($selectedRange, $rangeOptions)) {
            $selectedRange = '30d';
        }

        [$start, $end, $granularity] = $this->resolveWindow($rangeOptions[$selectedRange]);

        $paidInvoices = Invoice::query()
            ->where('status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'created_at', 'total_amount']);

        $consoleRecords = ConsoleSession::query()
            ->join('invoices', 'console_sessions.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->get([
                'console_sessions.id',
                'console_sessions.console_id',
                'console_sessions.total_price',
                'console_sessions.duration_minutes',
                'invoices.created_at as invoiced_at',
            ]);

        $tableRecords = TableSession::query()
            ->join('invoices', 'table_sessions.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->get([
                'table_sessions.id',
                'table_sessions.table_id',
                'table_sessions.total_price',
                'table_sessions.duration_minutes',
                'invoices.created_at as invoiced_at',
            ]);

        $boardGameRecords = BoardGameSession::query()
            ->join('invoices', 'board_game_sessions.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->get([
                'board_game_sessions.id',
                'board_game_sessions.board_game_id',
                'board_game_sessions.total_price',
                'board_game_sessions.duration_minutes',
                'invoices.created_at as invoiced_at',
            ]);

        $cafeRecords = Order::query()
            ->join('invoices', 'orders.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->get([
                'orders.id',
                'orders.total_price',
                'invoices.created_at as invoiced_at',
            ]);

        $totalRevenue = (float) $paidInvoices->sum('total_amount');
        $paidInvoiceCount = $paidInvoices->count();
        $averageInvoice = $paidInvoiceCount > 0 ? $totalRevenue / $paidInvoiceCount : 0;

        $consoleRevenue = $this->sumMoney($consoleRecords, 'total_price');
        $tableRevenue = $this->sumMoney($tableRecords, 'total_price');
        $boardGameRevenue = $this->sumMoney($boardGameRecords, 'total_price');
        $cafeRevenue = $this->sumMoney($cafeRecords, 'total_price');

        $categoryRows = [
            [
                'key' => 'console',
                'label' => 'کنسول‌ها',
                'revenue' => $consoleRevenue,
                'count' => $consoleRecords->count(),
            ],
            [
                'key' => 'table',
                'label' => 'میزها',
                'revenue' => $tableRevenue,
                'count' => $tableRecords->count(),
            ],
            [
                'key' => 'cafe',
                'label' => 'کافه',
                'revenue' => $cafeRevenue,
                'count' => $cafeRecords->count(),
            ],
            [
                'key' => 'board_game',
                'label' => 'بردگیم',
                'revenue' => $boardGameRevenue,
                'count' => $boardGameRecords->count(),
            ],
        ];

        $trendSeries = $this->buildTrendSeries(
            $paidInvoices,
            $consoleRecords,
            $tableRecords,
            $boardGameRecords,
            $cafeRecords,
            $start,
            $end,
            $granularity
        );

        $topConsoles = ConsoleSession::query()
            ->join('invoices', 'console_sessions.invoice_id', '=', 'invoices.id')
            ->leftJoin('consoles', 'console_sessions.console_id', '=', 'consoles.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->groupBy('console_sessions.console_id', 'consoles.name')
            ->orderByRaw('COALESCE(SUM(console_sessions.total_price), 0) DESC')
            ->limit(10)
            ->selectRaw('console_sessions.console_id')
            ->selectRaw('consoles.name as console_name')
            ->selectRaw('COUNT(console_sessions.id) as sessions_count')
            ->selectRaw('COALESCE(SUM(console_sessions.duration_minutes), 0) as total_minutes')
            ->selectRaw('COALESCE(SUM(console_sessions.total_price), 0) as revenue')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->console_name ?: 'کنسول حذف‌شده',
                    'sessions_count' => (int) $row->sessions_count,
                    'total_minutes' => (int) $row->total_minutes,
                    'revenue' => (float) $row->revenue,
                ];
            });

        $topTables = TableSession::query()
            ->join('invoices', 'table_sessions.invoice_id', '=', 'invoices.id')
            ->leftJoin('tables', 'table_sessions.table_id', '=', 'tables.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->groupBy('table_sessions.table_id', 'tables.name')
            ->orderByRaw('COALESCE(SUM(table_sessions.total_price), 0) DESC')
            ->limit(10)
            ->selectRaw('table_sessions.table_id')
            ->selectRaw('tables.name as table_name')
            ->selectRaw('COUNT(table_sessions.id) as sessions_count')
            ->selectRaw('COALESCE(SUM(table_sessions.duration_minutes), 0) as total_minutes')
            ->selectRaw('COALESCE(SUM(table_sessions.total_price), 0) as revenue')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->table_name ?: 'میز حذف‌شده',
                    'sessions_count' => (int) $row->sessions_count,
                    'total_minutes' => (int) $row->total_minutes,
                    'revenue' => (float) $row->revenue,
                ];
            });

        $topCafeItems = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('invoices', 'orders.invoice_id', '=', 'invoices.id')
            ->leftJoin('cafe_items', 'order_items.cafe_item_id', '=', 'cafe_items.id')
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.created_at', [$start, $end])
            ->groupBy('order_items.cafe_item_id', 'cafe_items.name')
            ->orderByRaw('COALESCE(SUM(order_items.total_price), 0) DESC')
            ->limit(10)
            ->selectRaw('order_items.cafe_item_id')
            ->selectRaw('cafe_items.name as item_name')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as quantity_sold')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->selectRaw('COALESCE(SUM(order_items.total_price), 0) as revenue')
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->item_name ?: 'آیتم حذف‌شده',
                    'quantity_sold' => (int) $row->quantity_sold,
                    'orders_count' => (int) $row->orders_count,
                    'revenue' => (float) $row->revenue,
                ];
            });

        $quickSummaries = $this->buildQuickSummaries();

        $dateWindow = sprintf(
            '%s تا %s',
            JalaliDate::format($start, 'Y/m/d H:i'),
            JalaliDate::format($end, 'Y/m/d H:i')
        );

        return view('accounting-stats.index', [
            'rangeOptions' => $rangeOptions,
            'selectedRange' => $selectedRange,
            'dateWindow' => $dateWindow,
            'quickSummaries' => $quickSummaries,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'paid_invoice_count' => $paidInvoiceCount,
                'average_invoice' => $averageInvoice,
                'console_revenue' => $consoleRevenue,
                'table_revenue' => $tableRevenue,
                'cafe_revenue' => $cafeRevenue,
                'board_game_revenue' => $boardGameRevenue,
            ],
            'categoryRows' => $categoryRows,
            'trendSeries' => $trendSeries,
            'topConsoles' => $topConsoles,
            'topTables' => $topTables,
            'topCafeItems' => $topCafeItems,
        ]);
    }

    protected function rangeOptions(): array
    {
        return [
            '1d' => ['label' => '۱ روزه', 'type' => 'days', 'value' => 1],
            '7d' => ['label' => '۷ روزه', 'type' => 'days', 'value' => 7],
            '30d' => ['label' => '۳۰ روزه', 'type' => 'days', 'value' => 30],
            '3m' => ['label' => '۳ ماهه', 'type' => 'months', 'value' => 3],
            '6m' => ['label' => '۶ ماهه', 'type' => 'months', 'value' => 6],
        ];
    }

    protected function resolveWindow(array $range): array
    {
        $now = now();
        $type = $range['type'] ?? 'days';
        $value = (int) ($range['value'] ?? 30);

        if ($type === 'months') {
            $start = $now->copy()->startOfMonth()->subMonthsNoOverflow(max($value - 1, 0));
            $end = $now->copy()->endOfDay();

            return [$start, $end, 'month'];
        }

        $start = $now->copy()->subDays(max($value - 1, 0))->startOfDay();
        $end = $now->copy()->endOfDay();
        $granularity = $value === 1 ? 'hour' : 'day';

        return [$start, $end, $granularity];
    }

    protected function buildQuickSummaries(): array
    {
        $now = now();
        $definitions = [
            '1d' => [
                'label' => '۱ روزه',
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            '7d' => [
                'label' => '۷ روزه',
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            '30d' => [
                'label' => '۳۰ روزه',
                'start' => $now->copy()->subDays(29)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            '6m' => [
                'label' => '۶ ماهه',
                'start' => $now->copy()->startOfMonth()->subMonthsNoOverflow(5),
                'end' => $now->copy()->endOfDay(),
            ],
        ];

        $result = [];

        foreach ($definitions as $key => $item) {
            $query = Invoice::query()
                ->where('status', 'paid')
                ->whereBetween('created_at', [$item['start'], $item['end']]);

            $result[$key] = [
                'label' => $item['label'],
                'revenue' => (float) (clone $query)->sum('total_amount'),
                'invoice_count' => (int) (clone $query)->count(),
            ];
        }

        return $result;
    }

    protected function buildTrendSeries(
        Collection $paidInvoices,
        Collection $consoleRecords,
        Collection $tableRecords,
        Collection $boardGameRecords,
        Collection $cafeRecords,
        Carbon $start,
        Carbon $end,
        string $granularity
    ): array {
        $buckets = $this->createBuckets($start, $end, $granularity);

        foreach ($paidInvoices as $invoice) {
            $key = $this->bucketKey(Carbon::parse($invoice->created_at), $granularity);
            if (! isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['total'] += (float) $invoice->total_amount;
        }

        $applyCategory = function (Collection $rows, string $field) use (&$buckets, $granularity): void {
            foreach ($rows as $row) {
                $key = $this->bucketKey(Carbon::parse($row->invoiced_at), $granularity);
                if (! isset($buckets[$key])) {
                    continue;
                }
                $buckets[$key][$field] += (float) ($row->total_price ?? 0);
            }
        };

        $applyCategory($consoleRecords, 'console');
        $applyCategory($tableRecords, 'table');
        $applyCategory($boardGameRecords, 'board_game');
        $applyCategory($cafeRecords, 'cafe');

        return array_values($buckets);
    }

    protected function createBuckets(Carbon $start, Carbon $end, string $granularity): array
    {
        $buckets = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $key = $this->bucketKey($cursor, $granularity);

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'key' => $key,
                    'label' => $this->bucketLabel($cursor, $granularity),
                    'total' => 0,
                    'console' => 0,
                    'table' => 0,
                    'board_game' => 0,
                    'cafe' => 0,
                ];
            }

            if ($granularity === 'hour') {
                $cursor->addHour();
                continue;
            }

            if ($granularity === 'month') {
                $cursor->addMonthNoOverflow()->startOfMonth();
                continue;
            }

            $cursor->addDay();
        }

        return $buckets;
    }

    protected function bucketKey(Carbon $date, string $granularity): string
    {
        if ($granularity === 'hour') {
            return $date->format('Y-m-d H');
        }

        if ($granularity === 'month') {
            return $date->format('Y-m');
        }

        return $date->toDateString();
    }

    protected function bucketLabel(Carbon $date, string $granularity): string
    {
        if ($granularity === 'hour') {
            return JalaliDate::format($date, 'H:00');
        }

        if ($granularity === 'month') {
            return JalaliDate::format($date, 'Y/m');
        }

        return JalaliDate::format($date, 'm/d');
    }

    protected function sumMoney(Collection $rows, string $column): float
    {
        return (float) $rows->sum(fn ($row) => (float) ($row->{$column} ?? 0));
    }
}
