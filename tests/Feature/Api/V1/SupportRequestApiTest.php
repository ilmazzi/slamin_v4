<?php

namespace Tests\Feature\Api\V1;

use App\Mail\SupportRequestMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SupportRequestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_send_a_support_request_with_an_attachment(): void
    {
        Mail::fake();

        $response = $this->post('/api/v1/support', [
            'name' => 'Mario Rossi',
            'email' => 'mario@example.com',
            'category' => 'technical',
            'subject' => 'Problema durante il caricamento',
            'message' => 'Il caricamento si interrompe prima di essere completato.',
            'attachments' => [
                UploadedFile::fake()->image('errore.png'),
            ],
        ], [
            'Accept' => 'application/json',
            'X-Locale' => 'it',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.message', 'Richiesta di supporto inviata.');

        Mail::assertSent(SupportRequestMail::class, function (SupportRequestMail $mail): bool {
            return $mail->hasTo('mail@slamin.it')
                && $mail->requesterEmail === 'mario@example.com'
                && $mail->category === 'technical'
                && count($mail->attachments()) === 1;
        });
    }

    public function test_support_request_validates_required_fields(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/support', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                ['name', 'email', 'category', 'subject', 'message'],
                'errors.fields',
            );

        Mail::assertNothingSent();
    }
}
