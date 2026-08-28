<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReenrollmentPayment;
use App\Models\Registration;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class ReenrollmentPaymentReceiptController extends Controller
{
    public function download(
        Registration $registration,
        ReenrollmentPayment $payment
    ): Response {
        /*
         * Payment pada URL wajib benar-benar milik
         * registration pada URL.
         */
        abort_unless(
            (int) $payment->registration_id
                === (int) $registration->id,
            404
        );

        $registration->load([
            'period.school',
            'major',
            'admissionPath',
        ]);

        $payment->load('receiver');

        /*
         * Bukti pembayaran harus bersifat historis.
         *
         * Hanya hitung transaksi yang terjadi sampai
         * transaksi yang sedang dicetak.
         *
         * ID dipakai sebagai tie-breaker apabila ada
         * beberapa pembayaran dengan paid_at sama.
         */
        $totalPaidAtTransaction =
            ReenrollmentPayment::query()
                ->where(
                    'registration_id',
                    $registration->id
                )
                ->where(function ($query) use ($payment) {
                    $query
                        ->where(
                            'paid_at',
                            '<',
                            $payment->paid_at
                        )
                        ->orWhere(function ($query) use ($payment) {
                            $query
                                ->where(
                                    'paid_at',
                                    $payment->paid_at
                                )
                                ->where(
                                    'id',
                                    '<=',
                                    $payment->id
                                );
                        });
                })
                ->sum('amount');

        $requiredFee = (int) (
            $registration->period
                ?->default_reenroll_fee ?? 0
        );

        $remainingAtTransaction = max(
            0,
            $requiredFee - $totalPaidAtTransaction
        );

        $receiptNumber = sprintf(
            'DU-%d-%06d',
            (int) $registration->period->year_start,
            (int) $payment->id
        );

        $filename =
            'bukti-pembayaran-'
            .$receiptNumber
            .'.pdf';

        $pdf = Pdf::loadView(
            'admin.reports.pdf.reenrollment-payment-receipt',
            [
                'registration' => $registration,
                'payment' => $payment,
                'requiredFee' => $requiredFee,
                'totalPaidAtTransaction' =>
                    (int) $totalPaidAtTransaction,
                'remainingAtTransaction' =>
                    (int) $remainingAtTransaction,
            ]
        )
            ->setPaper('a4', 'portrait');

        $response = $pdf->download($filename);

        /*
         * Header ini juga menjadi assertion point
         * deterministik untuk automated test.
         */
        $response->headers->set(
            'X-Receipt-Payment-Amount',
            (string) $payment->amount
        );

        $response->headers->set(
            'X-Receipt-Total-Paid',
            (string) $totalPaidAtTransaction
        );

        $response->headers->set(
            'X-Receipt-Remaining',
            (string) $remainingAtTransaction
        );

        return $response;
    }
}