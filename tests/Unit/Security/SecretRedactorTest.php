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

    public function test_it_redacts_every_pattern_and_a_configured_literal_in_a_single_pass(): void
    {
        $redactor = new SecretRedactor(['my-secret']);

        $this->assertSame(
            'Bearer [redacted] [redacted] config [redacted]',
            $redactor->redact('Bearer ghp_aaaaaaaaaaaaaaaa sk-bbbbbbbbbbbbbbbb config my-secret')
        );
    }

    public function test_it_redacts_across_newlines_in_a_multiline_payload(): void
    {
        $redactor = new SecretRedactor(['my-secret']);
        $payload = "Authorization: Bearer ghp_aaaaaaaaaaaaaaaa\nconfig=my-secret\nkey=sk-bbbbbbbbbbbbbbbb";

        $this->assertSame(
            "Authorization: Bearer [redacted]\nconfig=[redacted]\nkey=[redacted]",
            $redactor->redact($payload)
        );
    }

    public function test_it_leaves_text_without_secrets_unchanged(): void
    {
        $redactor = new SecretRedactor;

        $this->assertSame('connection timed out after 30s', $redactor->redact('connection timed out after 30s'));
    }
}
