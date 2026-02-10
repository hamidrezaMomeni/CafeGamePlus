<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['role'] = User::ROLE_STAFF;
        $data['permissions'] = $this->normalizePermissions($data['permissions'] ?? []);

        User::create($data);

        return redirect()->route('dashboard')->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    public function edit(User $user): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (! $request->filled('password')) {
            unset($data['password']);
        }

        if (! $user->isSuperAdmin()) {
            $data['role'] = User::ROLE_STAFF;
            $data['permissions'] = $this->normalizePermissions($data['permissions'] ?? []);
        } else {
            unset($data['permissions'], $data['role']);
        }

        $user->update($data);

        return redirect()->route('dashboard')->with('success', 'کاربر با موفقیت ویرایش شد.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            return redirect()->route('dashboard')->with('error', 'امکان حذف ادمین اصلی وجود ندارد.');
        }

        if (auth()->id() === $user->id) {
            return redirect()->route('dashboard')->with('error', 'امکان حذف حساب کاربری خودتان وجود ندارد.');
        }

        $user->delete();

        return redirect()->route('dashboard')->with('success', 'کاربر با موفقیت حذف شد.');
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<string, bool>
     */
    protected function normalizePermissions(array $permissions): array
    {
        $allowed = array_keys(User::availablePermissions());

        $clean = array_values(array_filter($permissions, fn ($p) => in_array($p, $allowed, true)));

        return array_fill_keys($clean, true);
    }
}
