<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OriginSchool;
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

        OriginSchool::create([
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

        return redirect()
            ->route('admin.origin-schools.index')
            ->with(
                'success',
                'Asal sekolah berhasil diperbarui.'
            );
    }

    public function toggle(
        OriginSchool $originSchool
    ): RedirectResponse {
        $originSchool->update([
            'is_active' => ! $originSchool->is_active,
        ]);

        return redirect()
            ->route('admin.origin-schools.index')
            ->with(
                'success',
                'Status asal sekolah berhasil diperbarui.'
            );
    }
}