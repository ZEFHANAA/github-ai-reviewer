<?php

namespace App\Support;

/**
 * Redacts credentials from text before it reaches logs. Provider-agnostic:
 * pattern rules cover common token shapes (Bearer, GitHub, OpenAI-style keys),
 * and configured literal secrets cover any provider added later.
 */
final class SecretRedactor
{
    public const PLACEHOLDER = '[redacted]';

    /** Token shapes that must never be logged, regardless of provider. */
    private const PATTERNS = [
        '/\bBearer\s+\K\S+/i',
        '/\bgh[pousr]_[A-Za-z0-9]{16,}/',
        '/\bgithub_pat_[A-Za-z0-9_]{16,}/',
        '/\bsk-[A-Za-z0-9._\-]{16,}/',
        '/\bxai-[A-Za-z0-9._\-]{16,}/',
    ];

    /** @var list<string> */
    private array $literals;

    /** @param array<int, string|null> $literals Known secret values from configuration. */
    public function __construct(array $literals = [])
    {
        $this->literals = array_values(array_filter(
            $literals,
            static fn (?string $literal): bool => is_string($literal) && trim($literal) !== '',
        ));
    }

    public function redact(string $message): string
    {
        foreach ($this->literals as $literal) {
            $message = str_replace($literal, self::PLACEHOLDER, $message);
        }

        foreach (self::PATTERNS as $pattern) {
            $message = (string) preg_replace($pattern, self::PLACEHOLDER, $message);
        }

        return $message;
    }
}
