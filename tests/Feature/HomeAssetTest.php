<?php

namespace Tests\Feature;

use App\Models\HomeAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $user = User::first();
        if ($user) {
            $this->actingAs($user);
        }
    }

    public function test_user_can_view_home_assets_list_with_summary_counts(): void
    {
        $response = $this->get('/home-assets');

        $response->assertStatus(200);
        $response->assertSee('Aset Rumah Saya');
        $response->assertSee('AC Ruang Tamu');
        $response->assertSee('Kondisi Normal');
    }

    public function test_user_can_create_home_asset(): void
    {
        $response = $this->post('/home-assets', [
            'name' => 'Kulkas 2 Pintu',
            'category' => 'dapur',
            'room' => 'Dapur Bersih',
            'brand' => 'LG',
            'model' => 'Smart Inverter GN-B215SQMT',
            'purchased_at' => '2024-03-01',
            'condition' => 'normal',
            'notes' => 'Pembersihan kondensor berkala',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('home_assets', [
            'name' => 'Kulkas 2 Pintu',
            'category' => 'dapur',
            'room' => 'Dapur Bersih',
            'brand' => 'LG',
        ]);
    }

    public function test_user_can_update_home_asset(): void
    {
        $asset = HomeAsset::firstOrFail();

        $response = $this->put("/home-assets/{$asset->id}", [
            'name' => 'AC Ruang Tamu (Master)',
            'category' => 'pendingin',
            'room' => 'Ruang Tamu Utama',
            'condition' => 'perlu_perhatian',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('home_assets', [
            'id' => $asset->id,
            'name' => 'AC Ruang Tamu (Master)',
            'room' => 'Ruang Tamu Utama',
            'condition' => 'perlu_perhatian',
        ]);
    }

    public function test_user_can_delete_home_asset(): void
    {
        $asset = HomeAsset::firstOrFail();

        $response = $this->delete("/home-assets/{$asset->id}");

        $response->assertRedirect('/home-assets');
        $this->assertDatabaseMissing('home_assets', [
            'id' => $asset->id,
        ]);
    }

    public function test_user_a_cannot_access_or_modify_user_b_home_asset(): void
    {
        $userA = User::firstOrFail();
        $userB = User::factory()->create([
            'email' => 'userb_asset@example.com',
        ]);

        $assetUserB = HomeAsset::create([
            'user_id' => $userB->id,
            'name' => 'CCTV Garasi User B',
            'category' => 'keamanan',
            'room' => 'Garasi Luar',
            'condition' => 'normal',
        ]);

        // User A cannot view User B's asset (404)
        $response = $this->actingAs($userA)->get("/home-assets/{$assetUserB->id}");
        $response->assertStatus(404);

        // User A cannot update User B's asset (404)
        $updateResponse = $this->actingAs($userA)->put("/home-assets/{$assetUserB->id}", [
            'name' => 'Hacked Asset',
            'category' => 'keamanan',
            'room' => 'Garasi Luar',
            'condition' => 'rusak',
        ]);
        $updateResponse->assertStatus(404);

        // User A cannot delete User B's asset (404)
        $deleteResponse = $this->actingAs($userA)->delete("/home-assets/{$assetUserB->id}");
        $deleteResponse->assertStatus(404);
    }
}
