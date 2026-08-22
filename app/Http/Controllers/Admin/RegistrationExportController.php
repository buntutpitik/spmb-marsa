<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RegistrationsExport;
use App\Http\Controllers\Controller;
use App\Models\PpdbPeriod;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistrationExportController extends Controller
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