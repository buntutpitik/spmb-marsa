<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class PublicRegistrationCardController extends Controller
{
    public function download(string $publicToken): Response
    {
        $registration = Registration::query()
            ->with([
                'period.school',
                'major',
                'admissionPath',
            ])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        return $this->pdf($registration);
    }

    private function pdf(Registration $registration): Response
    {
        $filename = 'kartu-pendaftaran-'
            .$registration->registration_number
            .'.pdf';

        return Pdf::loadView(
            'public.registration.card',
            [
                'registration' => $registration,
            ]
        )
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}