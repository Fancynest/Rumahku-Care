<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Rumahku Care');
    }

    public function test_home_assets_page_loads_successfully(): void
    {
        $response = $this->get('/home-assets');
        $response->assertStatus(200);
        $response->assertSee('Aset Rumah Saya');
    }

    public function test_home_asset_detail_page_loads_successfully(): void
    {
        $response = $this->get('/home-assets/1');
        $response->assertStatus(200);
        $response->assertSee('Detail Aset');
    }

    public function test_vehicles_page_loads_successfully(): void
    {
        $response = $this->get('/vehicles');
        $response->assertStatus(200);
        $response->assertSee('Kendaraan Saya');
        $response->assertSee('Beat Street');
    }

    public function test_technicians_page_loads_successfully(): void
    {
        $response = $this->get('/technicians');
        $response->assertStatus(200);
        $response->assertSee('Buku Kontak Teknisi');
    }
}
