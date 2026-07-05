<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_the_application_redirects_home_to_the_admin_dashboard()
    {
        $response = $this->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }
}
