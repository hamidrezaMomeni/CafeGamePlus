<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;

class CustomerController extends Controller
{
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
