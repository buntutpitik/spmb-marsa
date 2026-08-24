<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->query('q', '')
        );

        $action = trim(
            (string) $request->query('action', '')
        );

        $userId = $request->query('user_id');

        $logs = ActivityLog::query()
            ->with([
                'user:id,name,email,role',
                'registration:id,registration_number,full_name',
            ])

            ->when(
                $action !== '',
                fn (Builder $query) =>
                    $query->where('action', $action)
            )

            ->when(
                filled($userId),
                fn (Builder $query) =>
                    $query->where('user_id', $userId)
            )

            ->when(
                $search !== '',
                function (Builder $query) use ($search) {
                    $query->where(
                        function (Builder $query) use ($search) {
                            $query
                                ->where(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'action',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'metadata',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'user',
                                    function (Builder $userQuery) use ($search) {
                                        $userQuery
                                            ->where(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'email',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                )
                                ->orWhereHas(
                                    'registration',
                                    function (Builder $registrationQuery) use ($search) {
                                        $registrationQuery
                                            ->where(
                                                'registration_number',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'full_name',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )

            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $actions = ActivityLog::query()
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $users = User::query()
            ->whereIn('id', function ($query) {
                $query
                    ->select('user_id')
                    ->from('activity_logs')
                    ->whereNotNull('user_id');
            })
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
            ]);

        return view(
            'admin.activity-logs.index',
            [
                'logs' => $logs,
                'actions' => $actions,
                'users' => $users,
                'search' => $search,
                'selectedAction' => $action,
                'selectedUserId' => $userId,
            ]
        );
    }
}