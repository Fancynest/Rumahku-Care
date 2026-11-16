<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_activity_log(): void
    {
        $response = $this->get(route('activity-log.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_access_activity_log_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('activity-log.index'));

        $response->assertOk();
        $response->assertSee('Log Aktivitas');
        $response->assertSee('Semua perubahan dan kejadian tercatat secara detail di sini.');
    }

    public function test_user_only_sees_their_own_activity_logs(): void
    {
        $otherUser = User::factory()->create();

        ActivityLog::create([
            'user_id' => $this->user->id,
            'event' => 'home_asset.created',
            'description' => 'Aset milik user aktif dibuat',
        ]);

        ActivityLog::create([
            'user_id' => $otherUser->id,
            'event' => 'home_asset.created',
            'description' => 'Aset rahasia user lain',
        ]);

        $response = $this->actingAs($this->user)->get(route('activity-log.index'));

        $response->assertOk();
        $response->assertSee('Aset milik user aktif dibuat');
        $response->assertDontSee('Aset rahasia user lain');
    }

    public function test_activity_log_can_be_filtered_by_event(): void
    {
        ActivityLog::create([
            'user_id' => $this->user->id,
            'event' => 'vehicle.created',
            'description' => 'Motor Honda Beat baru',
        ]);

        ActivityLog::create([
            'user_id' => $this->user->id,
            'event' => 'home_asset.created',
            'description' => 'Kulkas Samsung baru',
        ]);

        $response = $this->actingAs($this->user)->get(route('activity-log.index', ['event' => 'vehicle']));

        $response->assertOk();
        $response->assertSee('Motor Honda Beat baru');
        $response->assertDontSee('Kulkas Samsung baru');
    }

    public function test_activity_logger_service_records_update_diff(): void
    {
        $logger = app(ActivityLogger::class);

        $old = ['name' => 'Kulkas Lama', 'room' => 'Dapur'];
        $new = ['name' => 'Kulkas Baru', 'room' => 'Dapur'];

        $asset = $this->user->homeAssets()->create([
            'name' => 'Kulkas Baru',
            'category' => 'dapur',
            'room' => 'Dapur',
            'condition' => 'normal',
            'condition_source' => 'manual',
        ]);

        $log = $logger->logUpdated(
            $this->user,
            'home_asset.updated',
            $asset,
            'Aset diperbarui',
            $old,
            $new
        );

        $this->assertNotNull($log);
        $this->assertDatabaseHas('activity_logs', [
            'id' => $log->id,
            'user_id' => $this->user->id,
            'event' => 'home_asset.updated',
            'description' => 'Aset diperbarui',
        ]);

        $this->assertArrayHasKey('changed', $log->properties);
        $this->assertEquals('Kulkas Lama', $log->properties['changed']['name']['old']);
        $this->assertEquals('Kulkas Baru', $log->properties['changed']['name']['new']);
        $this->assertArrayNotHasKey('room', $log->properties['changed']);
    }

    public function test_creating_home_asset_triggers_activity_log(): void
    {
        $response = $this->actingAs($this->user)->post(route('home-assets.store'), [
            'name' => 'AC Panasonic',
            'category' => 'pendingin',
            'room' => 'Kamar Utama',
            'condition' => 'normal',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'event' => 'home_asset.created',
        ]);
    }

    public function test_user_cascade_deletes_activity_logs(): void
    {
        $this->user->activityLogs()->create([
            'event' => 'custom.event',
            'description' => 'Testing user delete cascade',
        ]);

        $this->assertDatabaseCount('activity_logs', 1);

        $this->user->delete();

        $this->assertDatabaseCount('activity_logs', 0);
    }
}
