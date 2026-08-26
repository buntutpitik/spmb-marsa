<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $roles = [
            'SUPERADMIN' => 'Superadmin',
            'ADMIN' => 'Admin',
            'PANITIA' => 'Panitia',
            'BENDAHARA' => 'Bendahara',
        ];

        $query = User::query();

        if ($request->filled('q')) {
            $keyword = trim(
                (string) $request->input('q')
            );

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where(
                        'name',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$keyword}%"
                    );
            });
        }

        if (
            $request->filled('role')
            && array_key_exists(
                (string) $request->input('role'),
                $roles
            )
        ) {
            $query->where(
                'role',
                $request->input('role')
            );
        }

        if ($request->input('status') === 'ACTIVE') {
            $query->where(
                'is_active',
                true
            );
        }

        if ($request->input('status') === 'INACTIVE') {
            $query->where(
                'is_active',
                false
            );
        }

        $summary = [
            'total' => User::query()->count(),

            'active' => User::query()
                ->where('is_active', true)
                ->count(),

            'inactive' => User::query()
                ->where('is_active', false)
                ->count(),

            'superadmin' => User::query()
                ->where('role', 'SUPERADMIN')
                ->where('is_active', true)
                ->count(),
        ];

        $users = $query
            ->orderByRaw(
                "CASE role
                    WHEN 'SUPERADMIN' THEN 1
                    WHEN 'ADMIN' THEN 2
                    WHEN 'PANITIA' THEN 3
                    WHEN 'BENDAHARA' THEN 4
                    ELSE 5
                END"
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view(
            'admin.users.index',
            [
                'roles' => $roles,
                'summary' => $summary,
                'users' => $users,
            ]
        );
    }

    public function store(
        StoreUserRequest $request
    ): RedirectResponse {
        $user = DB::transaction(function () use ($request) {
            $user = User::query()->create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'role' => $request->validated('role'),
                'password' => $request->validated('password'),
                'is_active' => true,
            ]);

            $this->log(
                request: $request,
                action: 'CREATE_USER',
                description: 'User baru dibuat.',
                target: $user,
                metadata: [
                    'role' => $user->role,
                    'email' => $user->email,
                ]
            );

            return $user;
        });

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'User berhasil dibuat.'
            );
    }

    public function update(
        UpdateUserRequest $request,
        User $user
    ): RedirectResponse {
        $actor = $request->user();

        if (
            $actor->is($user)
            && $user->role === 'SUPERADMIN'
            && $request->validated('role') !== 'SUPERADMIN'
        ) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors([
                    'role' =>
                        'Anda tidak dapat menurunkan role akun sendiri.',
                ]);
        }

        if (
            $user->role === 'SUPERADMIN'
            && $user->is_active
            && $request->validated('role') !== 'SUPERADMIN'
            && $this->activeSuperadminCount() <= 1
        ) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors([
                    'role' =>
                        'SUPERADMIN aktif terakhir tidak dapat diturunkan.',
                ]);
        }

        $old = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        DB::transaction(function () use (
            $request,
            $user,
            $old
        ) {
            $user->update([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'role' => $request->validated('role'),
            ]);

            $this->log(
                request: $request,
                action: 'UPDATE_USER',
                description: 'Data user diperbarui.',
                target: $user,
                metadata: [
                    'old' => $old,
                    'new' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                ]
            );
        });

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'Data user berhasil diperbarui.'
            );
    }

    public function toggleActive(
        Request $request,
        User $user
    ): RedirectResponse {
        $actor = $request->user();

        if (
            $actor->is($user)
            && $user->is_active
        ) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors([
                    'user' =>
                        'Anda tidak dapat menonaktifkan akun sendiri.',
                ]);
        }

        if (
            $user->role === 'SUPERADMIN'
            && $user->is_active
            && $this->activeSuperadminCount() <= 1
        ) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors([
                    'user' =>
                        'SUPERADMIN aktif terakhir tidak dapat dinonaktifkan.',
                ]);
        }

        $oldStatus = $user->is_active;

        DB::transaction(function () use (
            $request,
            $user,
            $oldStatus
        ) {
            $user->update([
                'is_active' => ! $user->is_active,
            ]);

            $this->log(
                request: $request,
                action: 'TOGGLE_USER_ACTIVE',
                description: $user->is_active
                    ? 'User diaktifkan.'
                    : 'User dinonaktifkan.',
                target: $user,
                metadata: [
                    'from_is_active' => $oldStatus,
                    'to_is_active' => $user->is_active,
                ]
            );
        });

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                $user->is_active
                    ? 'User berhasil diaktifkan.'
                    : 'User berhasil dinonaktifkan.'
            );
    }

    public function resetPassword(
        ResetUserPasswordRequest $request,
        User $user
    ): RedirectResponse {
        DB::transaction(function () use (
            $request,
            $user
        ) {
            $user->update([
                'password' => $request->validated('password'),
            ]);

            $this->log(
                request: $request,
                action: 'RESET_USER_PASSWORD',
                description: 'Password user direset.',
                target: $user
            );
        });

        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'Password user berhasil direset.'
            );
    }

    private function activeSuperadminCount(): int
    {
        return User::query()
            ->where(
                'role',
                'SUPERADMIN'
            )
            ->where(
                'is_active',
                true
            )
            ->count();
    }

    private function log(
        Request $request,
        string $action,
        string $description,
        User $target,
        array $metadata = []
    ): void {
        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'registration_id' => null,
            'action' => $action,
            'description' => $description,
            'metadata' => array_merge(
                [
                    'target_user_id' => $target->id,
                    'target_name' => $target->name,
                    'target_email' => $target->email,
                ],
                $metadata
            ),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
