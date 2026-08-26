<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RegistrationsExport;
use App\Http\Controllers\Controller;
use App\Services\PeriodContext;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistrationExportController extends Controller
{
    public function __construct(
        protected PeriodContext $periodContext
    ) {
    }

    public function excel(
        Request $request
    ): BinaryFileResponse {
        $period = $this->periodContext
            ->resolveExplicitPeriod($request);

        $filename =
            'data-pendaftar-'
            .str_replace(
                '/',
                '-',
                $period->name
            )
            .'.xlsx';

        return Excel::download(
            new RegistrationsExport($period),
            $filename
        );
    }
}