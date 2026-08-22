<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReenrollmentFinanceExport;
use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReenrollmentFinanceExportController extends Controller
{
    public function excel(
        Request $request
    ): BinaryFileResponse {
        $period = PpdbPeriod::query()
            ->whereNull('archived_at')
            ->findOrFail(
                $request->integer('period_id')
            );

        $filename =
            'daftar-ulang-keuangan-'
            .str_replace(
                '/',
                '-',
                $period->name
            )
            .'.xlsx';

        return Excel::download(
            new ReenrollmentFinanceExport($period),
            $filename
        );
    }
}