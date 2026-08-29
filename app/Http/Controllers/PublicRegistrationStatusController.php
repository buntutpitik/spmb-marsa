<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Contracts\View\View;

class PublicRegistrationStatusController extends Controller
{
    public function show(string $publicToken): View
    {
        $registration = Registration::query()
            ->with([
                'period.school',
                'major',
                'admissionPath',
                'specialPrograms',
            ])
            ->where('public_token', $publicToken)
            ->firstOrFail();

        return view('public.registration.status', [
            'registration' => $registration,
        ]);
    }
}