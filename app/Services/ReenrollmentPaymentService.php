<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ReenrollmentPayment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReenrollmentPaymentService
{
    public function __construct(
        protected RegistrationStatusService $statusService,
        protected WhatsappNotificationService $notificationService
    ) {
    }

    public function addPayment(
        Registration $registration,
        int $amount,
        ?User $receiver = null,
        ?string $paymentMethod = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
        array $auditContext = []
    ): ReenrollmentPayment {
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Nominal pembayaran harus lebih dari 0.'
            );
        }

        return DB::transaction(function () use (
            $registration,
            $amount,
            $receiver,
            $paymentMethod,
            $referenceNumber,
            $auditContext,
            $notes
        ) {
            /*
             * Lock registration agar pembayaran dan perubahan status
             * tidak bertabrakan dengan request lain.
             */
            $registration = Registration::query()
                ->with([
                    'period',
                    'wave',
                ])
                ->lockForUpdate()
                ->findOrFail($registration->id);

            if ($registration->status !== 'ACCEPTED') {
                throw new InvalidArgumentException(
                    'Pembayaran daftar ulang hanya dapat dilakukan untuk pendaftar berstatus ACCEPTED.'
                );
            }

            $requiredFee = $this->requiredFee($registration);

            if ($requiredFee <= 0) {
                throw new InvalidArgumentException(
                    'Biaya daftar ulang belum dikonfigurasi.'
                );
            }

            /*
             * Hitung pembayaran yang sudah masuk.
             */
            $totalPaid = (int) ReenrollmentPayment::query()
                ->where(
                    'registration_id',
                    $registration->id
                )
                ->sum('amount');

            $remaining = max(
                0,
                $requiredFee - $totalPaid
            );

            if ($remaining <= 0) {
                throw new InvalidArgumentException(
                    'Biaya daftar ulang sudah lunas.'
                );
            }

            if ($amount > $remaining) {
                throw new InvalidArgumentException(
                    "Nominal pembayaran melebihi sisa tagihan {$remaining}."
                );
            }

            /*
             * Simpan transaksi pembayaran.
             */
            $payment = ReenrollmentPayment::create([
                'registration_id' => $registration->id,
                'amount' => $amount,
                'paid_at' => now(),
                'payment_method' => $paymentMethod,
                'reference_number' => $referenceNumber,
                'received_by' => $receiver?->id,
                'notes' => $notes,
            ]);

            $newTotalPaid = $totalPaid + $amount;

            $newRemaining = max(
                0,
                $requiredFee - $newTotalPaid
            );

            /*
             * Activity log pembayaran.
             */
           ActivityLog::create([
                'user_id' => $receiver?->id,
                'registration_id' => $registration->id,
                'action' => 'REENROLLMENT_PAYMENT',
                'description' => 'Pembayaran daftar ulang dicatat.',
                'metadata' => [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'required_fee' => $requiredFee,
                    'total_paid' => $newTotalPaid,
                    'remaining' => $newRemaining,
                ],
                'ip_address' =>
                    $auditContext['ip_address'] ?? null,
                'user_agent' =>
                    $auditContext['user_agent'] ?? null,
            ]);

            /*
             * Jika sudah lunas:
             *
             * 1. ubah status ACCEPTED -> REENROLLED
             * 2. refresh registration
             * 3. queue notifikasi daftar ulang selesai
             */
            if ($newRemaining === 0) {
                $registration = $this->statusService->change(
                    $registration,
                    'REENROLLED',
                    $receiver,
                    'Pembayaran daftar ulang telah lunas.',
                    $auditContext
                );

                $registration->refresh();

                $this->notificationService->reenrollmentComplete(
                    $registration
                );
            }

            return $payment->refresh();
        });
    }

    public function requiredFee(
        Registration $registration
    ): int {
        $registration->loadMissing([
            'period',
            'wave',
        ]);

        if (
            $registration->wave
            && $registration->wave->reenroll_fee !== null
        ) {
            return (int) $registration->wave->reenroll_fee;
        }

        return (int) $registration
            ->period
            ->default_reenroll_fee;
    }

    public function totalPaid(
        Registration $registration
    ): int {
        return (int) $registration
            ->reenrollmentPayments()
            ->sum('amount');
    }

    public function remaining(
        Registration $registration
    ): int {
        return max(
            0,
            $this->requiredFee($registration)
                - $this->totalPaid($registration)
        );
    }

    public function isPaidOff(
        Registration $registration
    ): bool {
        $requiredFee = $this->requiredFee(
            $registration
        );

        return $requiredFee > 0
            && $this->totalPaid($registration) >= $requiredFee;
    }
}