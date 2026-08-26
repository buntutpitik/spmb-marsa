<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OriginSchoolRecapExport;
use App\Http\Controllers\Controller;
use App\Services\PeriodContext;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OriginSchoolRecapExportController extends Controller
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
            'rekap-asal-sekolah-'
            .str_replace(
                '/',
                '-',
                $period->name
            )
            .'.xlsx';

        return Excel::download(
            new OriginSchoolRecapExport($period),
            $filename
        );
    }
}