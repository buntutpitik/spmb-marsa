<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\ReenrollmentPayment;
use App\Models\Registration;
use App\Models\School;
use App\Models\WhatsappLog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class WhatsappPaymentConstraintTest extends TestCase
{
    use DatabaseTransactions;

    private function makeRegistration(): Registration
    {
        $school = School::create(['name' => 'WA CONSTRAINT TEST']);
        $period = PpdbPeriod::create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_year' => 2027,
        ]);
        $path = AdmissionPath::create([
            'period_id' => $period->id,
            'name' => 'UMUM',
            'code' => 'WA-CONSTRAINT',
            'is_active' => true,
        ]);
        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'WAC',
            'name' => 'WA CONSTRAINT MAJOR',
            'is_active' => true,
        ]);

        return Registration::create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,
            'registration_number' => 'WA-CONSTRAINT-001',
            'nik' => '3311111111999001',
            'full_name' => 'WA CONSTRAINT STUDENT',
            'origin_school' => 'SMP TEST',
            'whatsapp' => '081211119999',
            'data_source' => 'ADMIN',
            'status' => 'ACCEPTED',
            'registered_at' => now(),
        ]);
    }

    private function makeLog(Registration $registration, ?int $paymentId, string $type): WhatsappLog
    {
        return WhatsappLog::create([
            'registration_id' => $registration->id,
            'reenrollment_payment_id' => $paymentId,
            'phone' => $registration->whatsapp,
            'message_type' => $type,
            'message' => 'Constraint test',
        ]);
    }

    public function test_different_payments_allow_same_message_type(): void
    {
        $registration = $this->makeRegistration();
        $first = ReenrollmentPayment::create(['registration_id' => $registration->id, 'amount' => 100000]);
        $second = ReenrollmentPayment::create(['registration_id' => $registration->id, 'amount' => 150000]);

        $this->makeLog($registration, $first->id, 'REENROLLMENT_PAYMENT_RECEIVED');
        $this->makeLog($registration, $second->id, 'REENROLLMENT_PAYMENT_RECEIVED');

        $this->assertSame(2, WhatsappLog::where('registration_id', $registration->id)
            ->where('message_type', 'REENROLLMENT_PAYMENT_RECEIVED')->count());
    }

    public function test_same_payment_rejects_duplicate_message_type(): void
    {
        $registration = $this->makeRegistration();
        $payment = ReenrollmentPayment::create(['registration_id' => $registration->id, 'amount' => 100000]);
        $this->makeLog($registration, $payment->id, 'REENROLLMENT_PAYMENT_RECEIVED');

        $this->expectException(QueryException::class);
        $this->makeLog($registration, $payment->id, 'REENROLLMENT_PAYMENT_RECEIVED');
    }

    public function test_same_payment_allows_different_message_types(): void
    {
        $registration = $this->makeRegistration();
        $payment = ReenrollmentPayment::create(['registration_id' => $registration->id, 'amount' => 100000]);

        $this->makeLog($registration, $payment->id, 'REENROLLMENT_PAYMENT_RECEIVED');
        $this->makeLog($registration, $payment->id, 'REENROLLMENT_PAYMENT_PAID_OFF');

        $this->assertSame(2, WhatsappLog::where('reenrollment_payment_id', $payment->id)->count());
    }

    public function test_legacy_event_rejects_duplicate(): void
    {
        $registration = $this->makeRegistration();
        $this->makeLog($registration, null, 'REGISTRATION_SUCCESS');

        $this->expectException(QueryException::class);
        $this->makeLog($registration, null, 'REGISTRATION_SUCCESS');
    }

    public function test_unknown_payment_rejected_by_foreign_key(): void
    {
        $registration = $this->makeRegistration();

        $this->expectException(QueryException::class);
        $this->makeLog($registration, 999999999, 'REENROLLMENT_PAYMENT_RECEIVED');
    }
}
