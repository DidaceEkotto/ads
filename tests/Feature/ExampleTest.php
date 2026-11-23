<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_accessible_to_visitors(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
