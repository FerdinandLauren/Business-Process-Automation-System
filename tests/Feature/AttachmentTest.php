<?php

namespace Tests\Feature;

use App\Models\RequestAttachment;
use App\Models\RequestType;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\AttachmentService;
use App\Services\RequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('bpa.attachments.disk'));

        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function attachmentFor(string $requestNumber): RequestAttachment
    {
        return ServiceRequest::where('request_number', $requestNumber)
            ->firstOrFail()
            ->attachments()
            ->firstOrFail();
    }

    public function test_the_requester_can_download_their_own_attachment(): void
    {
        $request = ServiceRequest::where('request_number', 'REQ-202611-0001')->firstOrFail();
        $attachment = $request->attachments()->firstOrFail();

        $response = $this->actingAs($this->user('ferdinand@arunika.test'))
            ->get(route('requests.attachments.download', [$request, $attachment]));

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString(
            $attachment->original_filename,
            $response->headers->get('content-disposition'),
        );
    }

    public function test_an_unrelated_employee_cannot_download_the_attachment(): void
    {
        $request = ServiceRequest::where('request_number', 'REQ-202611-0001')->firstOrFail();
        $attachment = $request->attachments()->firstOrFail();

        $this->actingAs($this->user('maya@arunika.test'))
            ->get(route('requests.attachments.download', [$request, $attachment]))
            ->assertForbidden();
    }

    public function test_an_assigned_approver_can_download_the_attachment(): void
    {
        $request = ServiceRequest::where('request_number', 'REQ-202611-0001')->firstOrFail();
        $attachment = $request->attachments()->firstOrFail();

        $this->actingAs($this->user('andi@arunika.test'))
            ->get(route('requests.attachments.download', [$request, $attachment]))
            ->assertOk();
    }

    public function test_an_attachment_cannot_be_downloaded_through_a_different_request(): void
    {
        $request = ServiceRequest::where('request_number', 'REQ-202611-0001')->firstOrFail();
        $other = ServiceRequest::where('request_number', 'REQ-202611-0002')->firstOrFail();
        $attachment = $request->attachments()->firstOrFail();

        $this->actingAs($this->user('ferdinand@arunika.test'))
            ->get(route('requests.attachments.download', [$other, $attachment]))
            ->assertNotFound();
    }

    public function test_a_disallowed_extension_is_rejected_before_it_is_stored(): void
    {
        $requester = $this->user('ferdinand@arunika.test');
        $request = app(RequestService::class)->save($requester, RequestType::where('code', 'REIMB')->firstOrFail(), [
            'title' => 'A claim with an executable attachment',
            'form_data' => [],
        ]);

        try {
            app(AttachmentService::class)->store(
                UploadedFile::fake()->create('payload.php', 1, 'application/x-php'),
                $request,
                $requester,
                'receipt',
            );
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('files', $exception->errors());
        }

        $this->assertSame(0, $request->attachments()->count());
    }

    public function test_only_the_requester_can_remove_a_draft_attachment(): void
    {
        $requester = $this->user('ferdinand@arunika.test');
        $request = app(RequestService::class)->save($requester, RequestType::where('code', 'REIMB')->firstOrFail(), [
            'title' => 'A claim with a receipt to remove',
            'form_data' => [],
        ]);

        $attachment = app(AttachmentService::class)->store(
            UploadedFile::fake()->create('receipt.pdf', 40, 'application/pdf'),
            $request,
            $requester,
            'receipt',
        );

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(AttachmentService::class)->delete($attachment, $this->user('maya@arunika.test'));
    }
}