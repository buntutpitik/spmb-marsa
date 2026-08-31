<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountPasswordRequest;
use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AccountPasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(
        UpdateAccountPasswordRequest $request
    ): RedirectResponse {
        $user = $request->user();

        DB::transaction(function () use (
            $request,
            $user
        ): void {
            $user->update([
                'password' => $request->validated('password'),
            ]);

            ActivityLog::create([
                'user_id' => $user->id,
                'registration_id' => null,
                'action' => 'CHANGE_OWN_PASSWORD',
                'description' => 'User mengubah password sendiri.',
                'metadata' => [
                    'target_user_id' => $user->id,
                    'target_name' => $user->name,
                    'target_email' => $user->email,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('account.password.edit')
            ->with(
                'success',
                'Password berhasil diperbarui.'
            );
    }
}