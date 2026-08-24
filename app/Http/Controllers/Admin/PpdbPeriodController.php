<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePpdbPeriodRequest;
use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PpdbPeriodController extends Controller
{
    public function index(): View
    {
        $periods = PpdbPeriod::query()
            ->with('school')
            ->whereNull('archived_at')
            ->orderByDesc('year_start')
            ->orderByDesc('id')
            ->get();

        return view(
            'admin.settings.periods.index',
            [
                'periods' => $periods,
            ]
        );
    }

    public function update(
        UpdatePpdbPeriodRequest $request,
        PpdbPeriod $period
    ): RedirectResponse {
        DB::transaction(function () use (
            $request,
            $period
        ) {
            $old = $period->only([
                'name',
                'year_start',
                'year_end',
                'registration_open',
                'registration_close',
                'status',
                'is_active',
                'principal_name',
                'principal_nip',
                'number_prefix',
                'number_year',
                'number_digits',
                'include_major_code',
                'default_reenroll_fee',
                'notes',
            ]);

            /*
             * Jika periode ini diaktifkan,
             * nonaktifkan semua periode lain.
             */
            if ($request->boolean('is_active')) {
                PpdbPeriod::query()
                    ->whereKeyNot($period->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                    ]);
            }

            $period->update([
                'name' =>
                    $request->validated('name'),

                'year_start' =>
                    $request->integer('year_start'),

                'year_end' =>
                    $request->integer('year_end'),

                'registration_open' =>
                    $request->validated(
                        'registration_open'
                    ),

                'registration_close' =>
                    $request->validated(
                        'registration_close'
                    ),

                'status' =>
                    $request->validated('status'),

                'is_active' =>
                    $request->boolean('is_active'),

                'principal_name' =>
                    $request->validated(
                        'principal_name'
                    ),

                'principal_nip' =>
                    $request->validated(
                        'principal_nip'
                    ),

                'number_prefix' =>
                    $request->validated(
                        'number_prefix'
                    ),

                'number_year' =>
                    $request->integer(
                        'number_year'
                    ),

                'number_digits' =>
                    $request->integer(
                        'number_digits'
                    ),

                'include_major_code' =>
                    $request->boolean(
                        'include_major_code'
                    ),

                'default_reenroll_fee' =>
                    $request->integer(
                        'default_reenroll_fee'
                    ),

                'notes' =>
                    $request->validated('notes'),
            ]);

            ActivityLog::create([
                'user_id' =>
                    $request->user()?->id,

                'registration_id' => null,

                'action' =>
                    'UPDATE_PPDB_PERIOD',

                'description' =>
                    'Pengaturan periode SPMB diperbarui.',

                'metadata' => [
                    'period_id' => $period->id,
                    'period_name' => $period->name,
                    'old' => $old,
                    'new' => $period->fresh()->only([
                        'name',
                        'year_start',
                        'year_end',
                        'registration_open',
                        'registration_close',
                        'status',
                        'is_active',
                        'principal_name',
                        'principal_nip',
                        'number_prefix',
                        'number_year',
                        'number_digits',
                        'include_major_code',
                        'default_reenroll_fee',
                        'notes',
                    ]),
                ],

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    $request->userAgent(),
            ]);
        });

        return redirect()
            ->route(
                'admin.periods.index'
            )
            ->with(
                'success',
                'Pengaturan periode berhasil diperbarui.'
            );
    }
}