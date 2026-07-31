<?php

namespace Tests\Unit\Security;

use App\Support\SecretRedactor;
use PHPUnit\Framework\TestCase;

class SecretRedactorTest extends TestCase
{
    public function test_it_redacts_a_bearer_token_from_any_message(): void
    {
        $redactor = new SecretRedactor;

        $this->assertSame(
            'request failed: Authorization: Bearer [redacted]',
            $redactor->redact('request failed: Authorization: Bearer ghp_abcdefghijklmnopqrstuvwxyz0123456789')
        );
    }

    public function test_it_redacts_known_github_token_prefixes_anywhere_in_the_text(): void
    {
        $redactor = new SecretRedactor;

        $this->assertSame(
            'token [redacted] rejected',
            $redactor->redact('token ghp_abcdefghijklmnopqrstuvwxyz0123456789 rejected')
        );
    }

    public function test_it_redacts_openai_style_keys(): void
    {
        $redactor = new SecretRedactor;

        $this->assertSame(
            'key=[redacted]',
            $redactor->redact('key=sk-abcdefghijklmnopqrstuvwxyz0123456789')
        );
    }

    public function test_it_redacts_configured_secret_values_from_any_provider(): void
    {
        $redactor = new SecretRedactor(['super-secret-value']);

        $this->assertSame(
            'provider rejected [redacted]',
            $redactor->redact('provider rejected super-secret-value')
        );
    }

    public function test_it_ignores_empty_configured_secrets_so_nothing_is_over_redacted(): void
    {
        $redactor = new SecretRedactor(['', null]);

        $this->assertSame('nothing sensitive here', $redactor->redact('nothing sensitive here'));
    }

    public function test_it_leaves_text_without_secrets_unchanged(): void
    {
        $redactor = new SecretRedactor;

        $this->assertSame('connection timed out after 30s', $redactor->redact('connection timed out after 30s'));
    }
}
