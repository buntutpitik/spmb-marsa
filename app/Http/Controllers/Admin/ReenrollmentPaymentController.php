<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReenrollmentPaymentRequest;
use App\Models\Registration;
use App\Services\ReenrollmentPaymentService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class ReenrollmentPaymentController extends Controller
{
    public function __construct(
        protected ReenrollmentPaymentService $paymentService
    ) {
    }

    public function store(
        StoreReenrollmentPaymentRequest $request,
        Registration $registration
    ): RedirectResponse {
        try {
            $payment = $this->paymentService->addPayment(
                $registration,
                $request->integer('amount'),
                $request->user(),
                $request->validated('payment_method'),
                $request->validated('reference_number'),
                $request->validated('notes')
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route(
                    'admin.registrations.show',
                    $registration
                )
                ->withErrors([
                    'payment' => $exception->getMessage(),
                ])
                ->withInput();
        }

        $registration->refresh();

        if ($registration->status === 'REENROLLED') {
            $message =
                'Pembayaran berhasil dicatat. Biaya daftar ulang telah lunas dan status pendaftar otomatis menjadi Daftar Ulang.';
        } else {
            $remaining = $this->paymentService->remaining(
                $registration
            );

            $message =
                'Pembayaran berhasil dicatat. Sisa tagihan: Rp '
                .number_format(
                    $remaining,
                    0,
                    ',',
                    '.'
                )
                .'.';
        }

        return redirect()
            ->route(
                'admin.registrations.show',
                $registration
            )
            ->with(
                'success',
                $message
            );
    }
}