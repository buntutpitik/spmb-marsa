<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePpdbPeriodRequest;
use App\Http\Requests\Admin\StorePpdbPeriodRequest;
use App\Models\School;
use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Services\Period\PeriodConfigurationCopyService;
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

    public function store(
        StorePpdbPeriodRequest $request,
        PeriodConfigurationCopyService $copyService
    ): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $copyService) {
            $school = School::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyExists = PpdbPeriod::query()
                ->where('school_id', $school->id)
                ->where('year_start', $data['year_start'])
                ->where('year_end', $data['year_end'])
                ->exists();

            if ($alreadyExists) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'year_start' => 'Periode tahun ajaran tersebut sudah tersedia.',
                ]);
            }

            $period = PpdbPeriod::query()->create([
                'school_id' => $school->id,
                'name' => $data['name'],
                'year_start' => $data['year_start'],
                'year_end' => $data['year_end'],
                'registration_open' =>
                    $data['registration_open'] ?? null,
                'registration_close' =>
                    $data['registration_close'] ?? null,
                'status' => 'DRAFT',
                'is_active' => false,
                'number_prefix' => $data['number_prefix'],
                'number_year' => $data['number_year'],
                'number_digits' => $data['number_digits'],
                'include_major_code' =>
                    $data['include_major_code'],
                'default_reenroll_fee' =>
                    $data['default_reenroll_fee'],
            ]);

            if (! empty($data['copy_from_period_id'])) {
                $source = PpdbPeriod::query()
                    ->whereKey($data['copy_from_period_id'])
                    ->where('school_id', $school->id)
                    ->whereNull('archived_at')
                    ->first();

                if (! $source) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'copy_from_period_id' =>
                            'Periode sumber tidak ditemukan pada sekolah ini.',
                    ]);
                }

                $copyService->copy($source, $period);
            }

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'registration_id' => null,
                'action' => 'CREATE_PPDB_PERIOD',
                'description' => 'Periode SPMB baru dibuat.',
                'metadata' => [
                    'period_id' => $period->id,
                    'period_name' => $period->name,
                    'school_id' => $school->id,
                    'status' => $period->status,
                    'is_active' => $period->is_active,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('admin.periods.index')
            ->with(
                'success',
                'Periode SPMB baru berhasil dibuat sebagai DRAFT.'
            );
    }

    public function update(
        UpdatePpdbPeriodRequest $request,
        PpdbPeriod $period
    ): RedirectResponse {
        $updated = DB::transaction(function () use (
            $request,
            $period
        ): bool {
            /*
            * Kunci baris sekolah terlebih dahulu.
            * Semua aktivasi periode pada sekolah yang sama
            * harus mengikuti urutan penguncian ini.
            */
            School::query()
                ->whereKey($period->school_id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            * Baca ulang periode setelah lock sekolah diperoleh.
            * Jangan mengandalkan data dari route model binding
            * karena statusnya mungkin sudah berubah.
            */
            $period = PpdbPeriod::query()
                ->whereKey($period->id)
                ->where('school_id', $period->school_id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
            * Periode CLOSED atau yang sudah diarsipkan
            * tidak boleh diperbarui.
            */
            if ($period->isReadOnly()) {
                return false;
            }

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

            $status = $request->validated('status');

            $isActive = $status === 'CLOSED'
                ? false
                : $request->boolean('is_active');

            /*
            * Jika periode diaktifkan, nonaktifkan periode
            * lain hanya dalam sekolah yang sama.
            */
            if ($isActive) {
                PpdbPeriod::query()
                    ->where('school_id', $period->school_id)
                    ->whereKeyNot($period->id)
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                    ]);
            }

            /*
            * Perbarui pengaturan periode.
            */
            $period->update([
                'name' =>
                    $request->validated('name'),

                'year_start' =>
                    $request->integer('year_start'),

                'year_end' =>
                    $request->integer('year_end'),

                'registration_open' =>
                    $request->validated('registration_open'),

                'registration_close' =>
                    $request->validated('registration_close'),

                'status' => $status,

                'is_active' => $isActive,

                'principal_name' =>
                    $request->validated('principal_name'),

                'principal_nip' =>
                    $request->validated('principal_nip'),

                'number_prefix' =>
                    $request->validated('number_prefix'),

                'number_year' =>
                    $request->integer('number_year'),

                'number_digits' =>
                    $request->integer('number_digits'),

                'include_major_code' =>
                    $request->boolean('include_major_code'),

                'default_reenroll_fee' =>
                    $request->integer('default_reenroll_fee'),

                'notes' =>
                    $request->validated('notes'),
            ]);

            /*
            * Catat audit log dalam transaksi yang sama.
            * Jika transaksi gagal, perubahan dan audit log
            * ikut di-rollback.
            */
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

            return true;
        }, 3);

        /*
        * Pembaruan ditolak jika periode CLOSED
        * atau sudah diarsipkan.
        */
        if (! $updated) {
            $message = $period->fresh()?->status === 'CLOSED'
                ? 'Periode yang sudah ditutup bersifat read-only dan tidak dapat diubah.'
                : 'Periode yang sudah diarsipkan tidak dapat diubah.';

            return redirect()
                ->route('admin.periods.index')
                ->with('error', $message);
        }

        return redirect()
            ->route('admin.periods.index')
            ->with(
                'success',
                'Pengaturan periode berhasil diperbarui.'
            );
    }
}
