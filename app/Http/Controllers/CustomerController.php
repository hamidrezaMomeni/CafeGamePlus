<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * جستجوی زندهٔ مشتری‌ها (AJAX) بر اساس نام، شماره موبایل و کد ملی.
     */
    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::withCount('invoices')
            ->search($search)
            ->latest()
            ->paginate(10, ['*'], 'customers_page')
            ->withPath(route('dashboard'))
            ->appends(['customer_search' => $search])
            ->fragment('customers');

        return response()->json([
            'rows' => view('customers._rows', ['customers' => $customers, 'search' => $search])->render(),
            'pagination' => $customers->links()->toHtml(),
            'total' => $customers->total(),
        ]);
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('dashboard')->with('success', 'مشتری با موفقیت اضافه شد');
    }

    public function show(Customer $customer): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function edit(Customer $customer): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('dashboard')->with('success', 'مشتری با موفقیت ویرایش شد');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('dashboard')->with('success', 'مشتری با موفقیت حذف شد');
    }
}
