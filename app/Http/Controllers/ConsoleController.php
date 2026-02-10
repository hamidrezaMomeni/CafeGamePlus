<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsoleRequest;
use App\Http\Requests\UpdateConsoleRequest;
use App\Models\Console;
use Illuminate\Http\RedirectResponse;

class ConsoleController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreConsoleRequest $request): RedirectResponse
    {
        Console::create($request->validated());

        return redirect()->route('dashboard')->with('success', 'کنسول شما با موفقیت اضافه شد');
    }

    public function edit(Console $console): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdateConsoleRequest $request, Console $console): RedirectResponse
    {
        $console->update($request->validated());

        return redirect()->route('dashboard')->with('success', 'کنسول شما با موفقیت ویرایش شد.');
    }

    public function destroy(Console $console): RedirectResponse
    {
        $console->delete();

        return redirect()->route('dashboard')->with('success', 'کنسول شما با موفقیت حذف شد.');
    }
}
