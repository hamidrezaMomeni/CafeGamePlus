<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\CafeItem;
use App\Models\Customer;
use App\Models\ConsoleSession;
use App\Models\TableSession;
use App\Models\BoardGameSession;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $totalPrice = 0;
        $orderItems = [];

        foreach ($request->items as $item) {
            $cafeItem = CafeItem::find($item['cafe_item_id']);
            $itemTotal = $cafeItem->price * $item['quantity'];
            $totalPrice += $itemTotal;

            $orderItems[] = [
                'cafe_item_id' => $item['cafe_item_id'],
                'quantity' => $item['quantity'],
                'price' => $cafeItem->price,
                'total_price' => $itemTotal,
            ];
        }

        $sessionRef = $request->validated('session_ref');
        $customer = $request->customer_id ? Customer::find($request->customer_id) : null;
        $tableId = $request->table_id;
        $invoice = null;

        if ($sessionRef && preg_match('/^(console|table|board_game):(\d+)$/', $sessionRef, $matches)) {
            [$type, $id] = [$matches[1], (int) $matches[2]];

            $session = match ($type) {
                'console' => ConsoleSession::with(['invoice', 'customer'])->where('status', 'active')->find($id),
                'table' => TableSession::with(['invoice', 'customer'])->where('status', 'active')->find($id),
                'board_game' => BoardGameSession::with(['invoice', 'customer'])->where('status', 'active')->find($id),
                default => null,
            };

            if (! $session) {
                return back()
                    ->withErrors(['session_ref' => 'سشن فعال انتخاب شده یافت نشد یا پایان یافته است.'])
                    ->withInput();
            }

            if ($session->customer) {
                $customer = $session->customer;
            }

            if ($type === 'table') {
                $tableId = $session->table_id;
            } elseif ($type !== 'table') {
                $tableId = null;
            }

            $invoice = $session->invoice;

            if (! $invoice) {
                $invoice = $this->invoiceService->createInvoice($customer, $totalPrice);
                $session->invoice_id = $invoice->id;
                $session->save();
            } elseif (! $invoice->customer_id && $customer) {
                $invoice->customer_id = $customer->id;
                $invoice->save();
            }
        }

        $order = Order::create([
            'customer_id' => $customer?->id,
            'table_id' => $tableId,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'invoice_id' => $invoice?->id,
        ]);

        foreach ($orderItems as $item) {
            $order->items()->create($item);
        }

        if (! $invoice) {
            $invoice = $this->invoiceService->createInvoice($customer, $totalPrice);
            $order->invoice_id = $invoice->id;
            $order->save();
        }

        if ($customer) {
            $customer->increment('total_spend', $totalPrice);
        }

        if ($invoice) {
            $this->invoiceService->updateInvoiceTotal($invoice);
        }

        return redirect()->route('dashboard')
            ->with('success', 'سفارش ثبت شد')
            ->with('open_invoice_id', $invoice->id);
    }

    public function show(Order $order): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
