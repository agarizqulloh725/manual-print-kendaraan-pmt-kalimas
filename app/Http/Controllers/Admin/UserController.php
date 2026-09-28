<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Administrators create operator accounts here; there is no public registration.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->withCount('tickets')
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.User::normalizePhone($search).'%');
                });
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('is_active', $status === 'active'))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $chips = array_values(array_filter([
            isset($filters['role']) ? ['label' => UserRole::from($filters['role'])->label(), 'remove' => ['role']] : null,
            isset($filters['status']) ? ['label' => $filters['status'] === 'active' ? 'Aktif' : 'Nonaktif', 'remove' => ['status']] : null,
        ]));

        return view('admin.users.index', [
            'users' => $users,
            'filters' => [
                'search' => $filters['search'] ?? null,
                'role' => $filters['role'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
            'chips' => $chips,
            'roles' => UserRole::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', ['roles' => UserRole::cases()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([...$request->validated(), 'is_active' => true]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Akun {$user->name} ({$user->phone}) berhasil dibuat.");
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->loadCount('tickets'),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->except('password');

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Akun {$user->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak bisa menghapus akun Anda sendiri.']);
        }

        if ($user->tickets()->exists()) {
            return back()->withErrors(['user' => "Akun {$user->name} sudah memiliki tiket, jadi tidak bisa dihapus. Nonaktifkan akun ini sebagai gantinya."]);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Akun {$user->name} berhasil dihapus.");
    }
}
