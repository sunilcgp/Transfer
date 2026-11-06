<?php

namespace Tests\Feature;

use App\Mail\TransferShared;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_selected_expiry_survives_file_count_and_size_updates(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->travelTo('2026-10-20 09:00:00');
        $user = User::factory()->create(['email' => 'sender@example.com']);

        $this->actingAs($user)->post(route('transfers.store'), [
            'from_email' => 'sender@example.com',
            'to_emails' => ['receiver@example.com'],
            'message' => 'Seven day transfer',
            'expires_in' => 7,
            'files' => [UploadedFile::fake()->create('report.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Your files were sent successfully.')
            ->assertJsonStructure(['redirect']);

        $transfer = Transfer::query()->latest('id')->firstOrFail();

        $this->assertSame('2026-10-27 09:00:00', $transfer->expires_at->format('Y-m-d H:i:s'));
        $this->assertSame(1, $transfer->file_count);
    }

    public function test_transfer_can_be_scheduled_and_is_sent_when_due(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->travelTo('2026-10-20 09:00:00');
        $user = User::factory()->create(['email' => 'sender@example.com']);

        $this->actingAs($user)->post(route('transfers.store'), [
            'from_email' => 'sender@example.com',
            'to_emails' => ['receiver@example.com'],
            'expires_in' => 7,
            'scheduled_at' => '2026-10-21T09:00:00Z',
            'files' => [UploadedFile::fake()->create('scheduled.pdf', 100, 'application/pdf')],
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('message', 'Your transfer has been scheduled successfully.')
            ->assertJsonPath('redirect', route('scheduled.index'));

        $transfer = Transfer::query()->latest('id')->firstOrFail();
        $this->assertNull($transfer->sent_at);
        $this->assertSame('2026-10-28 09:00:00', $transfer->expires_at->format('Y-m-d H:i:s'));
        Mail::assertNothingSent();
        $this->get(route('transfers.show', $transfer->token))->assertNotFound();

        $this->travelTo('2026-10-21 09:00:00');
        $this->artisan('transfers:send-scheduled')->assertSuccessful();
        $this->assertNotNull($transfer->refresh()->sent_at);
        Mail::assertSent(TransferShared::class);
    }

    public function test_guest_cannot_schedule_a_transfer(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->get('/')->assertOk()->assertDontSee('Schedule for later');

        $this->postJson(route('transfers.store'), [
            'from_email' => 'guest@example.com',
            'to_emails' => ['receiver@example.com'],
            'expires_in' => 7,
            'scheduled_at' => now()->addDay()->toIso8601String(),
            'files' => [UploadedFile::fake()->create('private.pdf', 100, 'application/pdf')],
        ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseCount('transfers', 0);
        Mail::assertNothingSent();
    }

    public function test_download_page_shows_rounded_expiry_header_footer_and_readable_file_sizes(): void
    {
        $this->travelTo('2026-10-20 09:00:20');
        $transfer = Transfer::factory()->create([
            'created_at' => '2026-10-20 09:00:00',
            'expires_at' => '2026-10-27 09:00:00',
            'file_count' => 2,
        ]);
        $transfer->files()->createMany([
            ['original_name' => 'small.pdf', 'path' => 'small.pdf', 'mime_type' => 'application/pdf', 'size' => 211155],
            ['original_name' => 'large.pdf', 'path' => 'large.pdf', 'mime_type' => 'application/pdf', 'size' => 2097152],
        ]);

        $this->get(route('transfers.show', $transfer->token))
            ->assertOk()
            ->assertSeeText('expires in 7 days')
            ->assertSeeText('206.2 KB')
            ->assertSeeText('2.0 MB')
            ->assertSeeText('Send files')
            ->assertSeeText('Private file transfer')
            ->assertSeeText('My Transfer')
            ->assertSeeText('Share link')
            ->assertSeeText('Share on WhatsApp');

        $this->assertDatabaseHas('transfer_events', [
            'transfer_id' => $transfer->id,
            'event_type' => 'link_opened',
        ]);
    }

    public function test_copy_and_whatsapp_share_actions_are_tracked_without_storing_raw_ip(): void
    {
        $transfer = Transfer::factory()->create(['expires_at' => now()->addDay()]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.15'])
            ->postJson(route('transfers.share.track', $transfer->token), ['channel' => 'copy'])
            ->assertOk();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.15'])
            ->postJson(route('transfers.share.track', $transfer->token), ['channel' => 'whatsapp'])
            ->assertOk();

        $this->assertDatabaseHas('transfer_events', ['transfer_id' => $transfer->id, 'event_type' => 'link_copied', 'channel' => 'copy']);
        $this->assertDatabaseHas('transfer_events', ['transfer_id' => $transfer->id, 'event_type' => 'whatsapp_shared', 'channel' => 'whatsapp']);
        $this->assertDatabaseMissing('transfer_events', ['ip_hash' => '203.0.113.15']);
    }

    public function test_downloading_a_file_records_the_first_download_time(): void
    {
        Storage::fake('local');
        $this->travelTo('2026-10-20 11:30:00');

        $transfer = Transfer::factory()->create([
            'expires_at' => now()->addDay(),
            'downloaded_at' => null,
        ]);
        $file = $transfer->files()->create([
            'original_name' => 'report.txt',
            'path' => 'transfers/'.$transfer->token.'/report.txt',
            'mime_type' => 'text/plain',
            'size' => 6,
        ]);
        Storage::disk('local')->put($file->path, 'report');

        $response = $this->get(route('transfers.download', [$transfer->token, $file->id]));

        $response->assertDownload('report.txt');
        $this->assertSame('2026-10-20 11:30:00', $transfer->refresh()->downloaded_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('transfer_events', [
            'transfer_id' => $transfer->id,
            'transfer_file_id' => $file->id,
            'event_type' => 'file_downloaded',
        ]);
    }

    public function test_later_downloads_do_not_replace_the_first_download_time(): void
    {
        Storage::fake('local');
        $this->travelTo('2026-10-20 11:30:00');

        $transfer = Transfer::factory()->create([
            'expires_at' => now()->addDay(),
            'downloaded_at' => '2026-10-19 08:00:00',
        ]);
        $file = $transfer->files()->create([
            'original_name' => 'report.txt',
            'path' => 'transfers/'.$transfer->token.'/report.txt',
            'mime_type' => 'text/plain',
            'size' => 6,
        ]);
        Storage::disk('local')->put($file->path, 'report');

        $response = $this->get(route('transfers.download', [$transfer->token, $file->id]));

        $response->assertDownload('report.txt');
        $this->assertSame('2026-10-19 08:00:00', $transfer->refresh()->downloaded_at->format('Y-m-d H:i:s'));
    }
}
