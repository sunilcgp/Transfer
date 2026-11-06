<?php

namespace Tests\Feature;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_screen(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirectToRoute('login');
    }

    public function test_dashboard_displays_only_the_authenticated_users_transfers(): void
    {
        $user = User::factory()->create([
            'name' => 'Aarav Sharma',
            'email' => 'aarav@example.com',
        ]);
        Transfer::factory()->create([
            'sender_email' => 'aarav@example.com',
            'file_count' => 3,
            'total_size' => 2048,
            'message' => 'Brand launch assets',
        ]);
        Transfer::factory()->create([
            'sender_email' => 'someone@example.com',
            'file_count' => 9,
            'message' => 'Private transfer from another user',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Good to see you,')
            ->assertSee('Aarav')
            ->assertSee('Brand launch assets')
            ->assertSee('3 files')
            ->assertDontSee('Private transfer from another user')
            ->assertDontSee('9 files');
    }

    public function test_dashboard_uses_transfer_ownership_when_sender_email_differs(): void
    {
        $user = User::factory()->create(['email' => 'account@example.com']);
        Transfer::factory()->create([
            'user_id' => $user->id,
            'sender_email' => 'custom-sender@example.com',
            'message' => 'Owned by account',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Owned by account');
    }

    public function test_dashboard_filters_reports_by_selected_period(): void
    {
        $this->travelTo('2026-10-20 12:00:00');
        $user = User::factory()->create();
        Transfer::factory()->create([
            'user_id' => $user->id,
            'message' => 'This week transfer',
            'created_at' => now()->subDays(2),
        ]);
        Transfer::factory()->create([
            'user_id' => $user->id,
            'message' => 'Older transfer',
            'created_at' => now()->subMonths(2),
        ]);

        $this->actingAs($user)->get(route('dashboard', ['period' => 'week']))
            ->assertOk()
            ->assertSee('This week transfer')
            ->assertDontSee('Older transfer')
            ->assertSee('Last 7 days');

        $this->get(route('dashboard', ['period' => 'quarter']))
            ->assertOk()
            ->assertSee('This week transfer')
            ->assertSee('Older transfer')
            ->assertSee('Last 3 months');
    }
}
