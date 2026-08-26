<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\WhatsappLog;
use App\Services\PeriodContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WhatsappLogController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext
    ) {
    }

    public function index(Request $request): View
    {
        /*
         * ---------------------------------------------------------
         * 1. Periode SPMB.
         * ---------------------------------------------------------
         */
        $periods = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->orderByDesc('year_start')
            ->get();

        $selectedPeriod = $this->periodContext
            ->resolveAdminPeriod($request);

        /*
         * ---------------------------------------------------------
         * 2. Status log yang tersedia.
         * ---------------------------------------------------------
         */
        $statuses = [
            'PENDING' => 'Menunggu',
            'SUCCESS' => 'Berhasil',
            'FAILED' => 'Gagal',
        ];

        /*
         * ---------------------------------------------------------
         * 3. Query utama.
         * ---------------------------------------------------------
         */
        $query = WhatsappLog::query()
            ->with([
                'registration:id,period_id,registration_number,full_name,whatsapp',
            ]);

        /*
         * Log yang tidak memiliki registration tetap boleh tampil.
         *
         * Jika periode dipilih:
         * - log terkait registration pada periode tersebut tampil;
         * - log tanpa registration tidak ikut karena tidak dapat
         *   dipastikan periodenya.
         */
        if ($selectedPeriod) {
            $query->whereHas(
                'registration',
                fn ($registrationQuery) =>
                    $registrationQuery->where(
                        'period_id',
                        $selectedPeriod->id
                    )
            );
        } else {
            /*
             * Tidak ada periode sama sekali berarti belum ada
             * konteks SPMB yang bisa ditampilkan.
             */
            $query->whereRaw('1 = 0');
        }

        /*
         * ---------------------------------------------------------
         * 4. Pencarian.
         * ---------------------------------------------------------
         *
         * Bisa mencari:
         * - nomor WhatsApp tujuan,
         * - nomor pendaftaran,
         * - nama calon siswa,
         * - provider message ID.
         */
        if ($request->filled('q')) {
            $keyword = trim(
                (string) $request->input('q')
            );

            $query->where(function ($subQuery) use ($keyword) {
                $subQuery
                    ->where(
                        'phone',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhere(
                        'provider_message_id',
                        'like',
                        "%{$keyword}%"
                    )
                    ->orWhereHas(
                        'registration',
                        function ($registrationQuery) use ($keyword) {
                            $registrationQuery
                                ->where(
                                    'registration_number',
                                    'like',
                                    "%{$keyword}%"
                                )
                                ->orWhere(
                                    'full_name',
                                    'like',
                                    "%{$keyword}%"
                                )
                                ->orWhere(
                                    'whatsapp',
                                    'like',
                                    "%{$keyword}%"
                                );
                        }
                    );
            });
        }

        /*
         * ---------------------------------------------------------
         * 5. Filter status.
         * ---------------------------------------------------------
         */
        if (
            $request->filled('status')
            && array_key_exists(
                (string) $request->input('status'),
                $statuses
            )
        ) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        /*
         * ---------------------------------------------------------
         * 6. Filter jenis pesan.
         * ---------------------------------------------------------
         */
        if ($request->filled('message_type')) {
            $query->where(
                'message_type',
                $request->input('message_type')
            );
        }

        /*
         * ---------------------------------------------------------
         * 7. Daftar jenis pesan untuk filter.
         * ---------------------------------------------------------
         */
        $messageTypes = WhatsappLog::query()
            ->whereNotNull('message_type')
            ->where('message_type', '!=', '');

        if ($selectedPeriod) {
            $messageTypes->whereHas(
                'registration',
                fn ($registrationQuery) =>
                    $registrationQuery->where(
                        'period_id',
                        $selectedPeriod->id
                    )
            );
        } else {
            $messageTypes->whereRaw('1 = 0');
        }

        $messageTypes = $messageTypes
            ->distinct()
            ->orderBy('message_type')
            ->pluck('message_type');

        /*
         * ---------------------------------------------------------
         * 8. Ringkasan status.
         * ---------------------------------------------------------
         */
        $summaryQuery = WhatsappLog::query();

        if ($selectedPeriod) {
            $summaryQuery->whereHas(
                'registration',
                fn ($registrationQuery) =>
                    $registrationQuery->where(
                        'period_id',
                        $selectedPeriod->id
                    )
            );
        } else {
            $summaryQuery->whereRaw('1 = 0');
        }

        $summary = [
            'total' => (clone $summaryQuery)->count(),

            'pending' => (clone $summaryQuery)
                ->where('status', 'PENDING')
                ->count(),

            'success' => (clone $summaryQuery)
                ->where('status', 'SUCCESS')
                ->count(),

            'failed' => (clone $summaryQuery)
                ->where('status', 'FAILED')
                ->count(),
        ];

        /*
         * ---------------------------------------------------------
         * 9. Pagination.
         * ---------------------------------------------------------
         */
        $logs = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.whatsapp-logs.index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'statuses' => $statuses,
            'messageTypes' => $messageTypes,
            'summary' => $summary,
            'logs' => $logs,
        ]);
    }
}