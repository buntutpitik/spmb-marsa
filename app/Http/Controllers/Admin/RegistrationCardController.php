<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class RegistrationCardController extends Controller
{
    public function download(
        Registration $registration
    ): Response {
        $registration->load([
            'period.school',
            'major',
            'admissionPath',
        ]);

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