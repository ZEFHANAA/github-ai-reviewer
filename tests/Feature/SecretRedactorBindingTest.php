<?php

namespace Tests\Feature;

use App\Support\SecretRedactor;
use Tests\TestCase;

class SecretRedactorBindingTest extends TestCase
{
    public function test_the_container_builds_a_redactor_that_hides_configured_provider_credentials(): void
    {
        config()->set('services.ai.key', 'ai-key-value');
        config()->set('services.github.token', 'github-token-value');

        $redactor = $this->app->make(SecretRedactor::class);

        $this->assertSame(
            'ai=[redacted] github=[redacted]',
            $redactor->redact('ai=ai-key-value github=github-token-value')
        );
    }
}
