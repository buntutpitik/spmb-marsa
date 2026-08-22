<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeRegistrationStatusRequest;
use App\Models\Registration;
use App\Services\RegistrationStatusService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class RegistrationStatusController extends Controller
{
    public function __construct(
        protected RegistrationStatusService $statusService
    ) {
    }

    public function update(
        ChangeRegistrationStatusRequest $request,
        Registration $registration
    ): RedirectResponse {
        try {
            $this->statusService->change(
                $registration,
                $request->validated('status'),
                $request->user(),
                $request->validated('notes')
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route(
                    'admin.registrations.show',
                    $registration
                )
                ->withErrors([
                    'status' => $exception->getMessage(),
                ]);
        }

        $statusLabel = match (
            $request->validated('status')
        ) {
            'ACCEPTED' => 'Diterima',
            'REJECTED' => 'Ditolak',
            'WITHDRAWN' => 'Mengundurkan Diri',
            default => $request->validated('status'),
        };

        return redirect()
            ->route(
                'admin.registrations.show',
                $registration
            )
            ->with(
                'success',
                "Status pendaftar berhasil diubah menjadi {$statusLabel}."
            );
    }
}