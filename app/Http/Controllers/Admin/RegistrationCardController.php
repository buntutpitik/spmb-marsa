<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\RegistrationStatusQrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class RegistrationCardController extends Controller
{
    public function download(
        Registration $registration,
        RegistrationStatusQrCode $qrCode
    ): Response {
        $registration->load([
            'period.school',
            'major',
            'admissionPath',
        ]);

        $filename = 'kartu-pendaftaran-'
            .$registration->registration_number
            .'.pdf';

        $statusUrl = $registration->public_token
            ? route(
                'registration.status',
                $registration->public_token
            )
            : null;

        return Pdf::loadView(
            'public.registration.card',
            [
                'registration' => $registration,
                'statusQrCode' => $qrCode->generate($statusUrl),
            ]
        )
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}