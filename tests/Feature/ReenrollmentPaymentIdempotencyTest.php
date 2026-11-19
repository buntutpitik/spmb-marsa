<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Services\ReenrollmentPaymentService;
use App\Jobs\SendWhatsappTemplateJob;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReenrollmentPaymentIdempotencyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_repeated_payment_with_same_uuid_creates_only_one_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::query()->create([
            'name' => 'SMK IDEMPOTENCY TEST',
            'npsn' => '88776655',
        ]);

        $period = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'IDEMP',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);

        $path = AdmissionPath::query()->create([
            'period_id' => $period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $major = Major::query()->create([
            'school_id' => $school->id,
            'code' => 'IDEMP',
            'name' => 'JURUSAN IDEMPOTENCY TEST',
            'short_name' => 'IDEMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $registration = Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,
            'registration_number' => 'IDEMP-TEST-'.Str::upper(Str::random(8)),
            'nik' => '338888'.random_int(1000000000, 9999999999),
            'full_name' => 'PENDAFTAR IDEMPOTENCY TEST',
            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',
            'origin_school' => 'SMP TEST',
            'whatsapp' => '081288889999',
            'data_source' => 'ADMIN',
            'status' => 'ACCEPTED',
            'created_by' => $user->id,
            'registered_at' => now(),
            'accepted_at' => now(),
        ]);

        $service = app(ReenrollmentPaymentService::class);
        $uuid = (string) Str::uuid();

        $firstPayment = $service->addPayment(
            registration: $registration,
            amount: 100000,
            receiver: $user,
            paymentMethod: 'CASH',
            idempotencyKey: $uuid
        );

        $secondPayment = $service->addPayment(
            registration: $registration,
            amount: 100000,
            receiver: $user,
            paymentMethod: 'CASH',
            idempotencyKey: $uuid
        );

        $this->assertSame(
            $firstPayment->id,
            $secondPayment->id
        );

        $this->assertSame(
            1,
            DB::table('reenrollment_payments')
                ->where('registration_id', $registration->id)
                ->count()
        );

        $this->assertSame(
            100000,
            (int) DB::table('reenrollment_payments')
                ->where('registration_id', $registration->id)
                ->sum('amount')
        );

        $this->assertSame(
            1,
            DB::table('activity_logs')
                ->where('registration_id', $registration->id)
                ->where('action', 'REENROLLMENT_PAYMENT')
                ->count()
        );

        /*
        * UUID yang sama tidak boleh digunakan
        * untuk pembayaran dengan nominal berbeda.
        */
        try {
            $service->addPayment(
                registration: $registration,
                amount: 50000,
                receiver: $user,
                paymentMethod: 'CASH',
                idempotencyKey: $uuid
            );

            $this->fail(
                'UUID yang sama dengan nominal berbeda harus ditolak.'
            );
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(
                'Identitas transaksi sudah digunakan untuk pembayaran berbeda.',
                $exception->getMessage()
            );
        }

        /*
        * Pastikan request yang ditolak tidak mengubah
        * jumlah maupun total pembayaran.
        */
        $this->assertSame(
            1,
            DB::table('reenrollment_payments')
                ->where('registration_id', $registration->id)
                ->count()
        );

        $this->assertSame(
            100000,
            (int) DB::table('reenrollment_payments')
                ->where('registration_id', $registration->id)
                ->sum('amount')
        );

        /*
        * UUID berbeda harus diperbolehkan sebagai
        * transaksi baru selama sisa tagihan mencukupi.
        */
        $anotherUuid = (string) Str::uuid();

        $thirdPayment = $service->addPayment(
            registration: $registration,
            amount: 100000,
            receiver: $user,
            paymentMethod: 'CASH',
            idempotencyKey: $anotherUuid
        );

        $this->assertNotEquals(
            $firstPayment->id,
            $thirdPayment->id
        );

        $this->assertSame(
            2,
            DB::table('reenrollment_payments')
                ->where('registration_id', $registration->id)
                ->count()
        );

        $this->assertSame(
            200000,
            (int) DB::table('reenrollment_payments')
                ->where('registration_id', $registration->id)
                ->sum('amount')
        );

        $this->assertSame(
            2,
            DB::table('activity_logs')
                ->where('registration_id', $registration->id)
                ->where('action', 'REENROLLMENT_PAYMENT')
                ->count()
        );

        /*
        * Pelunasan dan replay setelah tagihan lunas.
        */
        $settlementUuid = (string) Str::uuid();

        $settlement = $service->addPayment(
            registration: $registration,
            amount: 50000,
            receiver: $user,
            paymentMethod: 'CASH',
            idempotencyKey: $settlementUuid
        );

        $replayedSettlement = $service->addPayment(
            registration: $registration,
            amount: 50000,
            receiver: $user,
            paymentMethod: 'CASH',
            idempotencyKey: $settlementUuid
        );

        $this->assertSame(
            $settlement->id,
            $replayedSettlement->id
        );

        $this->assertSame(
            3,
            DB::table('reenrollment_payments')
                ->where('registration_id', $registration->id)
                ->count()
        );

        $this->assertSame(
            250000,
            (int) DB::table('reenrollment_payments')
                ->where('registration_id', $registration->id)
                ->sum('amount')
        );

        $this->assertSame(
            3,
            DB::table('activity_logs')
                ->where('registration_id', $registration->id)
                ->where('action', 'REENROLLMENT_PAYMENT')
                ->count()
        );

        /*
        * Setiap pembayaran sah menghasilkan satu log WhatsApp.
        * Replay tidak boleh menambah log notifikasi.
        */
        $paymentIds = [
            $firstPayment->id,
            $thirdPayment->id,
            $settlement->id,
        ];

        $this->assertSame(
            3,
            DB::table('whatsapp_logs')
                ->whereIn('reenrollment_payment_id', $paymentIds)
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('whatsapp_logs')
                ->where('reenrollment_payment_id', $firstPayment->id)
                ->where('message_type', 'REENROLLMENT_FIRST_PAYMENT')
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('whatsapp_logs')
                ->where('reenrollment_payment_id', $thirdPayment->id)
                ->where('message_type', 'REENROLLMENT_PAYMENT_RECEIVED')
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('whatsapp_logs')
                ->where('reenrollment_payment_id', $settlement->id)
                ->where('message_type', 'REENROLLMENT_PAYMENT_PAID_OFF')
                ->count()
        );

        /*
        * Tiga pembayaran sah harus menghasilkan tiga job.
        * Replay UUID tidak boleh menambahkan job keempat.
        */
        Queue::assertPushed(
            SendWhatsappTemplateJob::class,
            3
        );
    }
}
