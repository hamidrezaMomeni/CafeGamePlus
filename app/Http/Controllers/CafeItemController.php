<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCafeItemRequest;
use App\Http\Requests\UpdateCafeItemRequest;
use App\Models\CafeItem;
use Illuminate\Http\RedirectResponse;

class CafeItemController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreCafeItemRequest $request): RedirectResponse
    {
        CafeItem::create($request->validated());

        return redirect()->route('dashboard')->with('success', 'آیتم با موفقیت اضافه شد');
    }

    public function edit(CafeItem $cafeItem): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdateCafeItemRequest $request, CafeItem $cafeItem): RedirectResponse
    {
        $cafeItem->update($request->validated());

        return redirect()->route('dashboard')->with('success', 'آیتم با موفقیت ویرایش شد');
    }

    public function destroy(CafeItem $cafeItem): RedirectResponse
    {
        $cafeItem->delete();

        return redirect()->route('dashboard')->with('success', 'آیتم با موفقیت حذف شد');
    }
}
