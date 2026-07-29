<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class GitHubRepositoryUrl
{
    private const OWNER_PATTERN = '[A-Za-z0-9](?:[A-Za-z0-9-]{0,37}[A-Za-z0-9])?';

    private const REPOSITORY_PATTERN = '[A-Za-z0-9._-]+';

    private function __construct(
        public string $owner,
        public string $name,
        public string $canonicalUrl,
    ) {}

    public static function parse(string $url): self
    {
        $parts = parse_url($url);

        if ($parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || strtolower($parts['host'] ?? '') !== 'github.com'
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['port'])
            || array_key_exists('query', $parts)
            || array_key_exists('fragment', $parts)
        ) {
            throw new InvalidArgumentException('The URL must be a canonical public GitHub repository URL.');
        }

        $path = $parts['path'] ?? '';

        if (! preg_match('#^/('.self::OWNER_PATTERN.')/('.self::REPOSITORY_PATTERN.')/?$#', $path, $matches)) {
            throw new InvalidArgumentException('The URL must include exactly a GitHub owner and repository name.');
        }

        [$owner, $name] = [$matches[1], $matches[2]];

        if (str_ends_with(strtolower($name), '.git')) {
            throw new InvalidArgumentException('Git clone URLs are not supported; enter the repository page URL instead.');
        }

        return new self(
            owner: $owner,
            name: $name,
            canonicalUrl: "https://github.com/{$owner}/{$name}",
        );
    }
}
