<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;

class InvoiceService
{
    public function generateInvoiceNumber(): string
    {
        $lastInvoice = Invoice::query()->latest()->first();
        $number = $lastInvoice ? intval(substr($lastInvoice->invoice_number, 4)) + 1 : 1;
        return 'INV-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public function createInvoice(?Customer $customer, float $totalAmount): Invoice
    {
        return Invoice::create([
            'customer_id' => $customer?->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'total_amount' => $totalAmount,
            'status' => 'pending'
        ]);
    }

    public function updateInvoiceTotal(Invoice $invoice): void
    {
        $total = 0;

        foreach ($invoice->consoleSessions as $session) {
            $total += $session->total_price ?? 0;
        }

        foreach ($invoice->tableSessions as $session) {
            $total += $session->total_price ?? 0;
        }

        foreach ($invoice->boardGameSessions as $session) {
            $total += $session->total_price ?? 0;
        }

        foreach ($invoice->orders as $order) {
            $total += $order->total_price;
        }

        $invoice->update(['total_amount' => $total]);
    }
}
