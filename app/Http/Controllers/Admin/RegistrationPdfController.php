<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationPdfController extends Controller
{
    public function download(
        Request $request
    ): Response {
        $period = PpdbPeriod::query()
            ->with('school')
            ->whereNull('archived_at')
            ->findOrFail(
                $request->integer('period_id')
            );

        $registrations = Registration::query()
            ->with([
                'major',
                'admissionPath',
            ])
            ->where(
                'period_id',
                $period->id
            )
            ->orderBy('full_name')
            ->orderBy('id')
            ->get();

        $filename =
            'data-pendaftar-'
            .str_replace(
                '/',
                '-',
                $period->name
            )
            .'.pdf';

        return Pdf::loadView(
            'admin.reports.pdf.registrations',
            [
                'period' => $period,
                'registrations' => $registrations,
            ]
        )
            ->setPaper(
                'a4',
                'landscape'
            )
            ->download(
                $filename
            );
    }
}