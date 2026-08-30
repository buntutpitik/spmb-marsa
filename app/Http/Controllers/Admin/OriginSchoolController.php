<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OriginSchool;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OriginSchoolController extends Controller
{
    public function index(): View
    {
        $originSchools = OriginSchool::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.settings.origin-schools.index', [
            'originSchools' => $originSchools,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('origin_schools', 'name'),
            ],

            'type' => [
                'nullable',
                'string',
                'max:30',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],
        ], [
            'name.required' =>
                'Nama sekolah wajib diisi.',

            'name.unique' =>
                'Nama sekolah sudah ada pada master.',

            'name.max' =>
                'Nama sekolah maksimal 150 karakter.',

            'type.max' =>
                'Jenis sekolah maksimal 30 karakter.',

            'sort_order.required' =>
                'Urutan wajib diisi.',

            'sort_order.integer' =>
                'Urutan harus berupa angka.',
        ]);

        DB::transaction(function () use (
            $request,
            $validated
        ): void {
            $originSchool = OriginSchool::create([
                'name' => mb_strtoupper(
                    trim($validated['name'])
                ),

                'type' => filled($validated['type'] ?? null)
                    ? trim($validated['type'])
                    : null,

                'is_active' => true,

                'sort_order' =>
                    $validated['sort_order'],
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'CREATE_ORIGIN_SCHOOL',
                'description' => 'Asal sekolah ditambahkan.',
                'metadata' => [
                    'origin_school_id' => $originSchool->id,
                    'new' => $originSchool->only([
                        'name',
                        'type',
                        'is_active',
                        'sort_order',
                    ]),
                ],
            ]);
        });

        return redirect()
            ->route('admin.origin-schools.index')
            ->with(
                'success',
                'Asal sekolah berhasil ditambahkan.'
            );
    }

    public function update(
        Request $request,
        OriginSchool $originSchool
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('origin_schools', 'name')
                    ->ignore($originSchool->id),
            ],

            'type' => [
                'nullable',
                'string',
                'max:30',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],
        ], [
            'name.required' =>
                'Nama sekolah wajib diisi.',

            'name.unique' =>
                'Nama sekolah sudah ada pada master.',

            'name.max' =>
                'Nama sekolah maksimal 150 karakter.',

            'type.max' =>
                'Jenis sekolah maksimal 30 karakter.',

            'sort_order.required' =>
                'Urutan wajib diisi.',

            'sort_order.integer' =>
                'Urutan harus berupa angka.',
        ]);

        DB::transaction(function () use (
            $request,
            $validated,
            $originSchool
        ): void {
            $old = $originSchool->only([
                'name',
                'type',
                'sort_order',
            ]);

            $originSchool->update([
                'name' => mb_strtoupper(
                    trim($validated['name'])
                ),

                'type' => filled($validated['type'] ?? null)
                    ? trim($validated['type'])
                    : null,

                'sort_order' =>
                    $validated['sort_order'],
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'UPDATE_ORIGIN_SCHOOL',
                'description' => 'Asal sekolah diperbarui.',
                'metadata' => [
                    'origin_school_id' => $originSchool->id,
                    'old' => $old,
                    'new' => $originSchool
                        ->fresh()
                        ->only([
                            'name',
                            'type',
                            'sort_order',
                        ]),
                ],
            ]);
        });

        return redirect()
            ->route('admin.origin-schools.index')
            ->with(
                'success',
                'Asal sekolah berhasil diperbarui.'
            );
    }

    public function toggle(
        Request $request,
        OriginSchool $originSchool
    ): RedirectResponse {
        DB::transaction(function () use (
            $request,
            $originSchool
        ): void {
            $oldStatus = (bool) $originSchool->is_active;

            $originSchool->update([
                'is_active' => ! $oldStatus,
            ]);

            ActivityLog::create([
                'user_id' => $request->user()?->id,
                'registration_id' => null,
                'action' => 'TOGGLE_ORIGIN_SCHOOL',
                'description' => 'Status asal sekolah diperbarui.',
                'metadata' => [
                    'origin_school_id' => $originSchool->id,
                    'old' => [
                        'is_active' => $oldStatus,
                    ],
                    'new' => [
                        'is_active' =>
                            (bool) $originSchool->is_active,
                    ],
                ],
            ]);
        });

        return redirect()
            ->route('admin.origin-schools.index')
            ->with(
                'success',
                'Status asal sekolah berhasil diperbarui.'
            );
    }
}