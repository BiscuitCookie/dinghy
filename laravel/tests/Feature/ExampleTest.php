<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_spa(): void
    {
        $this->get('/')->assertRedirect('/app');
    }

    public function test_spa_page_is_served(): void
    {
        $this->withoutVite();

        $this->get('/app')->assertOk()->assertViewIs('spa');
    }
}
