<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_dashboard_returns_hrm_content(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dylan HRM');
        $response->assertSee('HRM Dashboard');
    }

    public function test_selected_hrm_screens_render_successfully(): void
    {
        $screens = [
            '/nhan-su' => 'Employee Center',
            '/cham-cong' => 'Attendance',
            '/luong-thuong' => 'Payroll',
            '/tuyen-dung' => 'Recruitment',
            '/bao-cao' => 'Reports',
        ];

        foreach ($screens as $path => $copy) {
            $this->get($path)
                ->assertStatus(200)
                ->assertSee($copy);
        }
    }
}
