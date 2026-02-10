<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTableRequest;
use App\Http\Requests\UpdateTableRequest;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;

class TableController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreTableRequest $request): RedirectResponse
    {
        Table::create($request->validated());

        return redirect()->route('dashboard')->with('success', 'میز با موفقیت اضافه شد');
    }

    public function edit(Table $table): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdateTableRequest $request, Table $table): RedirectResponse
    {
        $table->update($request->validated());

        return redirect()->route('dashboard')->with('success', 'میز با موفقیت ویرایش شد');
    }

    public function destroy(Table $table): RedirectResponse
    {
        $table->delete();

        return redirect()->route('dashboard')->with('success', 'میز با موفقیت حذف شد');
    }
}
