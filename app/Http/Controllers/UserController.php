<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'permissions', 'created_at'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'permissions' => $request->validated('permissions', []),
        ]);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Ensure permissions is always an array
        $data['permissions'] = $data['permissions'] ?? [];

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
