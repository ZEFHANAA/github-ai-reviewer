<?php

namespace Tests\Feature;

use Tests\TestCase;

class TestEnvironmentTest extends TestCase
{
    public function test_the_application_boots_in_the_testing_environment(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertTrue(app()->runningUnitTests());
    }
}
