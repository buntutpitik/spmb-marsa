<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ReferralSource;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReferralSourceController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.referral-sources.index', [
            'referralSources' => ReferralSource::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => mb_strtoupper(
                trim((string) $request->input('name', ''))
            ),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('referral_sources', 'name'),
            ],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $source = ReferralSource::create([
                'name' => mb_strtoupper(trim($validated['name'])),
                'is_active' => true,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'CREATE_REFERRAL_SOURCE',
                'description' => 'Asal pembawa ditambahkan.',
                'metadata' => [
                    'referral_source_id' => $source->id,
                    'new' => $source->only(['name', 'is_active']),
                ],
            ]);
        });

        return redirect()
            ->route('admin.referral-sources.index')
            ->with('success', 'Asal pembawa berhasil ditambahkan.');
    }

    public function update(
        Request $request,
        ReferralSource $referralSource
    ): RedirectResponse {
        $request->merge([
            'name' => mb_strtoupper(
                trim((string) $request->input('name', ''))
            ),
        ]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('referral_sources', 'name')
                    ->ignore($referralSource->id),
            ],
        ]);

        DB::transaction(function () use (
            $request,
            $validated,
            $referralSource
        ): void {
            $old = $referralSource->only(['name', 'is_active']);

            $referralSource->update([
                'name' => mb_strtoupper(trim($validated['name'])),
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'UPDATE_REFERRAL_SOURCE',
                'description' => 'Asal pembawa diperbarui.',
                'metadata' => [
                    'referral_source_id' => $referralSource->id,
                    'old' => $old,
                    'new' => $referralSource->fresh()
                        ->only(['name', 'is_active']),
                ],
            ]);
        });

        return redirect()
            ->route('admin.referral-sources.index')
            ->with('success', 'Asal pembawa berhasil diperbarui.');
    }

    public function toggle(
        Request $request,
        ReferralSource $referralSource
    ): RedirectResponse {
        DB::transaction(function () use ($request, $referralSource): void {
            $oldStatus = (bool) $referralSource->is_active;

            $referralSource->update([
                'is_active' => ! $oldStatus,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'TOGGLE_REFERRAL_SOURCE',
                'description' => 'Status asal pembawa diperbarui.',
                'metadata' => [
                    'referral_source_id' => $referralSource->id,
                    'old' => ['is_active' => $oldStatus],
                    'new' => [
                        'is_active' => (bool) $referralSource->is_active,
                    ],
                ],
            ]);
        });

        return redirect()
            ->route('admin.referral-sources.index')
            ->with('success', 'Status asal pembawa berhasil diperbarui.');
    }
}
