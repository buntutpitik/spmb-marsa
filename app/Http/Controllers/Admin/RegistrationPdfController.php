<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\PeriodContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationPdfController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext
    ) {
    }

    public function download(
        Request $request
    ): Response {
        $period = $this->periodContext
            ->resolveExplicitPeriod($request);

        $period->load('school');

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