<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBoardGameRequest;
use App\Http\Requests\UpdateBoardGameRequest;
use App\Models\BoardGame;
use Illuminate\Http\RedirectResponse;

class BoardGameController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreBoardGameRequest $request): RedirectResponse
    {
        BoardGame::create($request->validated());

        return redirect()->route('dashboard')->with('success', 'بردگیم با موفقیت اضافه شد');
    }

    public function edit(BoardGame $boardGame): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdateBoardGameRequest $request, BoardGame $boardGame): RedirectResponse
    {
        $boardGame->update($request->validated());

        return redirect()->route('dashboard')->with('success', 'بردگیم با موفقیت ویرایش شد');
    }

    public function destroy(BoardGame $boardGame): RedirectResponse
    {
        $boardGame->delete();

        return redirect()->route('dashboard')->with('success', 'بردگیم با موفقیت حذف شد');
    }
}
