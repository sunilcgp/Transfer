<?php

namespace Tests\Feature;

use App\Mail\TransferShared;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransferReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_screen(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_guest_cannot_access_report_data(): void
    {
        $response = $this->get(route('reports.data'));

        $response->assertRedirectToRoute('login');
    }

    public function test_report_page_displays_the_datatable(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertSeeText('Sent transfer list')
            ->assertSee('id="transferReportsTable"', false)
            ->assertSee(route('reports.data'), false);
    }

    public function test_report_data_lists_only_the_authenticated_users_transfer_details(): void
    {
        $this->travelTo('2026-10-20 10:15:00');

        $user = User::factory()->create([
            'email' => 'sender@example.com',
        ]);
        $transfer = Transfer::factory()->create([
            'sender_email' => 'sender@example.com',
            'recipient_emails' => ['receiver@example.com', 'accounts@example.com'],
            'message' => 'Quarterly delivery files',
            'file_count' => 2,
            'total_size' => 2048,
            'created_at' => '2026-10-20 10:15:00',
            'expires_at' => '2026-10-27 18:30:00',
            'downloaded_at' => '2026-10-21 09:45:00',
        ]);
        $transfer->files()->create([
            'original_name' => 'quarterly-report.pdf',
            'path' => 'transfers/report.pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
        ]);
        $transfer->events()->createMany([
            ['event_type' => 'link_opened', 'ip_hash' => 'device-a', 'created_at' => now()],
            ['event_type' => 'link_opened', 'ip_hash' => 'device-b', 'created_at' => now()],
            ['event_type' => 'link_copied', 'channel' => 'copy', 'ip_hash' => 'device-a', 'created_at' => now()],
            ['event_type' => 'whatsapp_shared', 'channel' => 'whatsapp', 'ip_hash' => 'device-a', 'created_at' => now()],
            ['event_type' => 'file_downloaded', 'ip_hash' => 'device-b', 'created_at' => now()],
        ]);
        Transfer::factory()->create([
            'sender_email' => 'another@example.com',
            'message' => 'Private report from another sender',
        ]);

        $response = $this->actingAs($user)->getJson(route('reports.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 15,
            'order' => [['column' => 0, 'dir' => 'desc']],
        ]));

        $response->assertJsonPath('draw', 1)
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.sender_email', 'sender@example.com')
            ->assertJsonPath('data.0.recipient_emails.0', 'receiver@example.com')
            ->assertJsonPath('data.0.recipient_emails.1', 'accounts@example.com')
            ->assertJsonPath('data.0.message', 'Quarterly delivery files')
            ->assertJsonPath('data.0.file_names.0', 'quarterly-report.pdf')
            ->assertJsonPath('data.0.sent_date', '20 Oct 2026')
            ->assertJsonPath('data.0.sent_time', '10:15 AM')
            ->assertJsonPath('data.0.expiry_date', '27 Oct 2026')
            ->assertJsonPath('data.0.expiry_time', '06:30 PM')
            ->assertJsonPath('data.0.download_date', '21 Oct 2026')
            ->assertJsonPath('data.0.download_time', '09:45 AM')
            ->assertJsonPath('data.0.status', 'downloaded')
            ->assertJsonPath('data.0.activity.copies', 1)
            ->assertJsonPath('data.0.activity.whatsapp', 1)
            ->assertJsonPath('data.0.activity.opens', 2)
            ->assertJsonPath('data.0.activity.downloads', 1)
            ->assertJsonPath('data.0.activity.devices', 2)
            ->assertJsonMissing(['message' => 'Private report from another sender']);
    }

    public function test_report_data_can_be_searched(): void
    {
        $user = User::factory()->create([
            'email' => 'sender@example.com',
        ]);
        Transfer::factory()->create([
            'sender_email' => 'sender@example.com',
            'message' => 'Quarterly delivery files',
        ]);
        Transfer::factory()->create([
            'sender_email' => 'sender@example.com',
            'message' => 'Holiday photographs',
        ]);

        $response = $this->actingAs($user)->getJson(route('reports.data', [
            'draw' => 2,
            'start' => 0,
            'length' => 15,
            'search' => ['value' => 'Quarterly'],
        ]));

        $response->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.message', 'Quarterly delivery files')
            ->assertJsonCount(1, 'data');
    }

    public function test_report_uses_transfer_ownership_when_sender_email_differs(): void
    {
        $user = User::factory()->create(['email' => 'account@example.com']);
        $otherUser = User::factory()->create();
        Transfer::factory()->create([
            'user_id' => $user->id,
            'sender_email' => 'custom-sender@example.com',
            'message' => 'Owned transfer',
        ]);
        Transfer::factory()->create([
            'user_id' => $otherUser->id,
            'sender_email' => 'account@example.com',
            'message' => 'Another user transfer',
        ]);

        $response = $this->actingAs($user)->getJson(route('reports.data'));

        $response->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.message', 'Owned transfer')
            ->assertJsonMissing(['message' => 'Another user transfer']);
    }

    public function test_user_can_view_only_files_from_their_own_transfer(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedTransfer = Transfer::factory()->create(['user_id' => $user->id]);
        $ownedFile = $ownedTransfer->files()->create([
            'original_name' => 'owned-report.pdf',
            'path' => 'transfers/owned-report.pdf',
            'mime_type' => 'application/pdf',
            'size' => 7,
        ]);
        Storage::put($ownedFile->path, 'content');

        $otherTransfer = Transfer::factory()->create(['user_id' => $otherUser->id]);
        $otherFile = $otherTransfer->files()->create([
            'original_name' => 'private-report.pdf',
            'path' => 'transfers/private-report.pdf',
            'mime_type' => 'application/pdf',
            'size' => 7,
        ]);
        Storage::put($otherFile->path, 'private');

        $this->actingAs($user)
            ->get(route('reports.files.view', $ownedFile))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=owned-report.pdf');

        $this->get(route('reports.files.view', $otherFile))->assertNotFound();
    }

    public function test_user_can_resend_only_their_active_transfer(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $transfer = Transfer::factory()->create([
            'user_id' => $user->id,
            'recipient_emails' => ['first@example.com', 'second@example.com'],
            'sent_at' => now(),
            'expires_at' => now()->addDay(),
        ]);
        $otherTransfer = Transfer::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)->postJson(route('reports.resend', $transfer))
            ->assertOk()
            ->assertJsonPath('message', 'Transfer email sent again successfully.');
        Mail::assertSent(TransferShared::class, fn (TransferShared $mail): bool => $mail->transfer->is($transfer));

        $this->postJson(route('reports.resend', $otherTransfer))->assertNotFound();
    }

    public function test_expired_transfer_cannot_be_resent(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $transfer = Transfer::factory()->create([
            'user_id' => $user->id,
            'sent_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->postJson(route('reports.resend', $transfer))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This transfer has expired and cannot be resent.');
        Mail::assertNothingSent();
    }
}
