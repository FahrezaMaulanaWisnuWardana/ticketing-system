<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ExampleTest extends TestCase
{
    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
