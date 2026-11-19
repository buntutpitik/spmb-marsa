<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappTemplateJob;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsappLog;
use App\Services\ReenrollmentPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReenrollmentPaymentNotificationFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2027-02-15 10:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_three_payments_queue_correct_distinct_notifications(): void
    {
        $user = User::factory()->create(['name' => 'BENDAHARA TEST', 'role' => 'ADMIN', 'is_active' => true]);
        $school = School::query()->create(['name' => 'SMK PAYMENT NOTIFICATION TEST', 'npsn' => '87777777']);
        $period = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'NTF',
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
            'code' => 'NTF',
            'name' => 'JURUSAN NOTIFICATION TEST',
            'short_name' => 'NTF',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $registration = Registration::query()->create([
            'period_id' => $period->id,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,
            'registration_number' => 'NOTIFICATION-TEST-0001',
            'nik' => '3388888888999999',
            'nisn' => null,
            'full_name' => 'PENDAFTAR NOTIFICATION TEST',
            'birth_place' => 'KEBUMEN',
            'birth_date' => '2010-01-01',
            'gender' => 'L',
            'religion' => 'ISLAM',
            'origin_school' => 'SMP NOTIFICATION TEST',
            'whatsapp' => '081288889999',
            'data_source' => 'ADMIN',
            'status' => 'ACCEPTED',
            'created_by' => $user->id,
            'registered_at' => now(),
            'accepted_at' => now(),
        ]);
        $service = app(ReenrollmentPaymentService::class);
        $cases = [
            [100000, 'REENROLLMENT_FIRST_PAYMENT', 'reenrollment_first_payment', 'Rp100.000', 'Rp100.000', 'Rp150.000', 'Belum Lunas'],
            [50000, 'REENROLLMENT_PAYMENT_RECEIVED', 'reenrollment_payment_received', 'Rp50.000', 'Rp150.000', 'Rp100.000', 'Belum Lunas'],
            [100000, 'REENROLLMENT_PAYMENT_PAID_OFF', 'reenrollment_payment_paid_off', 'Rp100.000', 'Rp250.000', 'Rp0', 'Lunas'],
        ];

        foreach ($cases as $index => [$amount, $type, $template, $paid, $total, $remaining, $status]) {
            $payment = $service->addPayment($registration, $amount, $user);
            $registration->refresh();
            $this->assertSame('REENROLLED', $registration->status);
            $this->assertNotNull($registration->reenrolled_at);
            $log = WhatsappLog::query()->where('reenrollment_payment_id', $payment->id)->firstOrFail();
            $this->assertSame($type, $log->message_type);
            $this->assertSame($registration->id, $log->registration_id);
            $this->assertSame('PENDING', $log->status);
            $this->assertSame(0, $log->attempt_count);
            $expectedParameters = [$registration->full_name, $registration->registration_number, $paid, $total, $remaining, $status];
            Queue::assertPushed(SendWhatsappTemplateJob::class, function ($job) use ($log, $template, $expectedParameters) {
                return $job->whatsappLogId === $log->id
                    && $job->templateName === $template
                    && $job->languageCode === 'id'
                    && $job->bodyParameters === $expectedParameters;
            });
            $this->assertSame($index + 1, WhatsappLog::query()->where('registration_id', $registration->id)->count());
        }

        $this->assertSame(0, $service->remaining($registration));
        $this->assertTrue($service->isPaidOff($registration));
        $this->assertSame(1, DB::table('registration_status_histories')->where('registration_id', $registration->id)->where('to_status', 'REENROLLED')->count());
        $this->assertSame(3, DB::table('reenrollment_payments')->where('registration_id', $registration->id)->count());
        Queue::assertPushed(SendWhatsappTemplateJob::class, 3);
        $this->assertDatabaseMissing('whatsapp_logs', ['registration_id' => $registration->id, 'message_type' => 'REENROLLMENT_COMPLETE']);
    }
}
