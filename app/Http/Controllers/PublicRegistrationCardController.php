<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\RegistrationStatusQrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PublicRegistrationCardController extends Controller
{
    public function download(
        string $publicToken,
        RegistrationStatusQrCode $qrCode
    ): Response {
        $registration = Registration::query()
            ->with([
                'period.school',
                'major',
                'admissionPath',
            ])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        return $this->pdf(
            $registration,
            $qrCode
        );
    }

    private function pdf(
        Registration $registration,
        RegistrationStatusQrCode $qrCode
    ): Response {
        $filename = 'kartu-pendaftaran-'
            .$registration->registration_number
            .'.pdf';

        $statusUrl = route(
            'registration.status',
            $registration->public_token
        );

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