<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BrevoMailTransportTest extends TestCase
{
    use RefreshDatabase;

    private function configureBrevo(): void
    {
        Mail::purge('brevo');
        Config::set('mail.default', 'brevo');
        Config::set('services.brevo.key', 'xkeysib-test-secret-key');
        Config::set('mail.from.address', 'sender@citycollegeoftagaytay.edu.ph');
        Config::set('mail.from.name', 'CCT Wellness Portal');
    }

    public function test_brevo_transport_maps_all_fields_and_sends_https_request(): void
    {
        $this->configureBrevo();

        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<msg-123@brevo>'], 201),
        ]);

        Mail::send([], [], function ($message) {
            $message->from('verified@citycollegeoftagaytay.edu.ph', 'Guidance Office')
                ->to('student@citycollegeoftagaytay.edu.ph', 'Juan Dela Cruz')
                ->cc('counselor@citycollegeoftagaytay.edu.ph', 'Counselor')
                ->bcc('audit@citycollegeoftagaytay.edu.ph')
                ->replyTo('reply@citycollegeoftagaytay.edu.ph', 'Support')
                ->subject('Test Subject')
                ->html('<p>Hello <strong>Student</strong></p>')
                ->text('Hello Student');
        });

        Http::assertSent(function (HttpClientRequest $request) {
            $data = $request->data();

            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $request->method() === 'POST'
                && $request->hasHeader('api-key', 'xkeysib-test-secret-key')
                && ($data['sender']['email'] ?? null) === 'verified@citycollegeoftagaytay.edu.ph'
                && ($data['sender']['name'] ?? null) === 'Guidance Office'
                && ($data['to'][0]['email'] ?? null) === 'student@citycollegeoftagaytay.edu.ph'
                && ($data['to'][0]['name'] ?? null) === 'Juan Dela Cruz'
                && ($data['cc'][0]['email'] ?? null) === 'counselor@citycollegeoftagaytay.edu.ph'
                && ($data['bcc'][0]['email'] ?? null) === 'audit@citycollegeoftagaytay.edu.ph'
                && ($data['replyTo']['email'] ?? null) === 'reply@citycollegeoftagaytay.edu.ph'
                && ($data['subject'] ?? null) === 'Test Subject'
                && ($data['htmlContent'] ?? null) === '<p>Hello <strong>Student</strong></p>'
                && ($data['textContent'] ?? null) === 'Hello Student';
        });
    }

    public function test_brevo_transport_handles_api_error_without_leaking_key(): void
    {
        $this->configureBrevo();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Brevo API rejected email request.'
                    && ($context['status'] ?? null) === 403
                    && ($context['code'] ?? null) === 'permission_denied'
                    && ($context['message'] ?? null) === 'Unrecognized IP address [REDACTED] blocked'
                    && ! str_contains(json_encode($context), 'xkeysib-test-secret-key')
                    && ! str_contains(json_encode($context), 'student@citycollegeoftagaytay.edu.ph');
            });

        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response([
                'code' => "permission_denied\n",
                'message' => "Unrecognized IP address\r\nxkeysib-test-secret-key\tblocked",
                'extra_secret' => 'should_not_be_logged',
            ], 403),
        ]);

        try {
            Mail::raw('Test body', fn ($m) => $m->to('student@citycollegeoftagaytay.edu.ph')->subject('Fail Test'));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('HTTP 403, permission_denied: Unrecognized IP address [REDACTED] blocked', $e->getMessage());
            $this->assertStringNotContainsString('xkeysib-test-secret-key', $e->getMessage());
            $this->assertStringNotContainsString('should_not_be_logged', $e->getMessage());
        }
    }

    public function test_brevo_transport_handles_malformed_or_non_json_error_response(): void
    {
        $this->configureBrevo();

        Log::shouldReceive('warning')
            ->once()
            ->with('Brevo API rejected email request.', [
                'status' => 502,
                'code' => null,
                'message' => null,
            ]);

        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response('<html>Bad Gateway</html>', 502),
        ]);

        try {
            Mail::raw('Test body', fn ($m) => $m->to('student@citycollegeoftagaytay.edu.ph')->subject('Malformed Test'));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Brevo API rejected email request (HTTP 502).', $e->getMessage());
        }
    }

    public function test_brevo_transport_handles_timeout_without_leaking_key(): void
    {
        $this->configureBrevo();

        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);

        try {
            Mail::raw('Test body', fn ($m) => $m->to('student@citycollegeoftagaytay.edu.ph')->subject('Timeout Test'));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('timed out', $e->getMessage());
            $this->assertStringNotContainsString('xkeysib-test-secret-key', $e->getMessage());
        }
    }

    public function test_email_verification_preserves_signed_link_via_brevo(): void
    {
        $this->configureBrevo();

        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<verify-1@brevo>'], 201),
        ]);

        $user = User::factory()->unverified()->create([
            'role' => 'student',
            'email' => 'verify.student@citycollegeoftagaytay.edu.ph',
        ]);

        $user->sendEmailVerificationNotification();

        $capturedHtml = '';
        Http::assertSent(function (HttpClientRequest $request) use (&$capturedHtml) {
            $data = $request->data();
            $capturedHtml = (string) ($data['htmlContent'] ?? '');

            return ($data['to'][0]['email'] ?? null) === 'verify.student@citycollegeoftagaytay.edu.ph'
                && str_contains($capturedHtml, '/verify-email/');
        });

        preg_match('/https?:\/\/[^\s"\'<]+verify-email\/[^\s"\'<]+/', $capturedHtml, $matches);
        $this->assertNotEmpty($matches[0] ?? null);

        $verifyUrl = html_entity_decode($matches[0], ENT_QUOTES, 'UTF-8');
        $this->actingAs($user)->get($verifyUrl)->assertRedirect(route('dashboard', absolute: false) . '?verified=1');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_password_reset_notification_preserves_reset_link_via_brevo(): void
    {
        $this->configureBrevo();

        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<reset-1@brevo>'], 201),
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'reset.student@citycollegeoftagaytay.edu.ph',
        ]);

        $user->sendPasswordResetNotification('brevo-test-reset-token');

        Http::assertSent(function (HttpClientRequest $request) {
            $data = $request->data();
            $html = (string) ($data['htmlContent'] ?? '');

            return ($data['to'][0]['email'] ?? null) === 'reset.student@citycollegeoftagaytay.edu.ph'
                && str_contains($html, 'brevo-test-reset-token');
        });
    }
}
