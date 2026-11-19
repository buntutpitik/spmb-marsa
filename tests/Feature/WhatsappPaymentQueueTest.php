<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappTemplateJob;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\ReenrollmentPayment;
use App\Models\Registration;
use App\Models\School;
use App\Models\WhatsappLog;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsappPaymentQueueTest extends TestCase
{
    use RefreshDatabase;

    private function makeRegistration(): Registration
    {
        $school = School::create(['name' => 'WA PAYMENT QUEUE TEST']);
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
            'code' => 'WA-QUEUE',
            'is_active' => true,
        ]);
        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'WAQ',
            'name' => 'WA QUEUE MAJOR',
            'is_active' => true,
        ]);

        return Registration::create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,
            'registration_number' => 'WA-QUEUE-001',
            'nik' => '3311111111999002',
            'full_name' => 'WA QUEUE STUDENT',
            'origin_school' => 'SMP TEST',
            'whatsapp' => '081211118888',
            'data_source' => 'ADMIN',
            'status' => 'ACCEPTED',
            'registered_at' => now(),
        ]);
    }

    public function test_same_payment_event_only_creates_one_log_and_job(): void
    {
        Queue::fake();
        $registration = $this->makeRegistration();
        $payment = ReenrollmentPayment::create([
            'registration_id' => $registration->id,
            'amount' => 100000,
        ]);
        $service = app(WhatsappNotificationService::class);
        $params = [$registration->full_name, 'Rp100.000'];

        $first = $service->queuePayment($payment, 'reenrollment_first_payment', $params);
        $second = $service->queuePayment($payment, 'reenrollment_first_payment', $params);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($payment->id, $first->reenrollment_payment_id);
        $this->assertSame($registration->id, $first->registration_id);
        $this->assertSame(1, WhatsappLog::where('reenrollment_payment_id', $payment->id)->count());
        Queue::assertPushed(SendWhatsappTemplateJob::class, 1);
    }

    public function test_different_payments_can_queue_same_event(): void
    {
        Queue::fake();
        $registration = $this->makeRegistration();
        $firstPayment = ReenrollmentPayment::create([
            'registration_id' => $registration->id,
            'amount' => 100000,
        ]);
        $secondPayment = ReenrollmentPayment::create([
            'registration_id' => $registration->id,
            'amount' => 50000,
        ]);
        $service = app(WhatsappNotificationService::class);

        $first = $service->queuePayment($firstPayment, 'reenrollment_payment_received', ['Pertama']);
        $second = $service->queuePayment($secondPayment, 'reenrollment_payment_received', ['Kedua']);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, WhatsappLog::where('registration_id', $registration->id)
            ->where('message_type', 'REENROLLMENT_PAYMENT_RECEIVED')->count());
        Queue::assertPushed(SendWhatsappTemplateJob::class, 2);
    }

    public function test_payment_event_does_not_change_legacy_event_idempotency(): void
    {
        Queue::fake();
        $registration = $this->makeRegistration();
        $payment = ReenrollmentPayment::create([
            'registration_id' => $registration->id,
            'amount' => 100000,
        ]);
        $service = app(WhatsappNotificationService::class);

        $legacy = $service->registrationSuccess($registration);
        $service->queuePayment($payment, 'reenrollment_first_payment', ['Pembayaran']);
        $legacyAgain = $service->registrationSuccess($registration);

        $this->assertSame($legacy->id, $legacyAgain->id);
        $this->assertSame(2, WhatsappLog::where('registration_id', $registration->id)->count());
        Queue::assertPushed(SendWhatsappTemplateJob::class, 2);
    }
}
