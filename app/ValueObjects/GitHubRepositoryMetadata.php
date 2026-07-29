<?php

namespace App\ValueObjects;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class GitHubRepositoryMetadata
{
    /**
     * @param  list<string>  $topics
     */
    private function __construct(
        public string $fullName,
        public string $url,
        public string $owner,
        public string $name,
        public ?string $description,
        public ?string $defaultBranch,
        public ?string $primaryLanguage,
        public int $starsCount,
        public int $forksCount,
        public int $openIssuesCount,
        public int $watchersCount,
        public ?int $subscribersCount,
        public int $sizeKilobytes,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public ?CarbonImmutable $pushedAt,
        public bool $isArchived,
        public bool $isFork,
        public ?string $visibility,
        public ?string $licenseName,
        public ?string $licenseSpdxId,
        public array $topics,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromGitHubResponse(array $payload): self
    {
        $license = $payload['license'] ?? null;

        if ($license !== null && ! is_array($license)) {
            throw new InvalidArgumentException('The GitHub response contained an invalid license value.');
        }

        return new self(
            fullName: self::requiredString($payload, 'full_name'),
            url: self::requiredString($payload, 'html_url'),
            owner: self::requiredNestedString($payload, 'owner', 'login'),
            name: self::requiredString($payload, 'name'),
            description: self::nullableString($payload, 'description'),
            defaultBranch: self::nullableString($payload, 'default_branch'),
            primaryLanguage: self::nullableString($payload, 'language'),
            starsCount: self::requiredInteger($payload, 'stargazers_count'),
            forksCount: self::requiredInteger($payload, 'forks_count'),
            openIssuesCount: self::requiredInteger($payload, 'open_issues_count'),
            watchersCount: self::requiredInteger($payload, 'watchers_count'),
            subscribersCount: self::nullableInteger($payload, 'subscribers_count'),
            sizeKilobytes: self::requiredInteger($payload, 'size'),
            createdAt: self::nullableDate($payload, 'created_at'),
            updatedAt: self::nullableDate($payload, 'updated_at'),
            pushedAt: self::nullableDate($payload, 'pushed_at'),
            isArchived: self::requiredBoolean($payload, 'archived'),
            isFork: self::requiredBoolean($payload, 'fork'),
            visibility: self::nullableString($payload, 'visibility'),
            licenseName: self::nullableString($license ?? [], 'name'),
            licenseSpdxId: self::nullableString($license ?? [], 'spdx_id'),
            topics: self::topics($payload),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function requiredString(array $payload, string $key): string
    {
        if (! isset($payload[$key]) || ! is_string($payload[$key])) {
            throw new InvalidArgumentException("The GitHub response is missing a valid {$key} value.");
        }

        return $payload[$key];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function requiredNestedString(array $payload, string $parent, string $key): string
    {
        if (! isset($payload[$parent]) || ! is_array($payload[$parent])) {
            throw new InvalidArgumentException("The GitHub response is missing a valid {$parent} value.");
        }

        return self::requiredString($payload[$parent], $key);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function nullableString(array $payload, string $key): ?string
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        if (! is_string($payload[$key])) {
            throw new InvalidArgumentException("The GitHub response contained an invalid {$key} value.");
        }

        return $payload[$key];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function requiredInteger(array $payload, string $key): int
    {
        if (! isset($payload[$key]) || ! is_int($payload[$key])) {
            throw new InvalidArgumentException("The GitHub response is missing a valid {$key} value.");
        }

        return $payload[$key];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function nullableInteger(array $payload, string $key): ?int
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null) {
            return null;
        }

        if (! is_int($payload[$key])) {
            throw new InvalidArgumentException("The GitHub response contained an invalid {$key} value.");
        }

        return $payload[$key];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function requiredBoolean(array $payload, string $key): bool
    {
        if (! isset($payload[$key]) || ! is_bool($payload[$key])) {
            throw new InvalidArgumentException("The GitHub response is missing a valid {$key} value.");
        }

        return $payload[$key];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function nullableDate(array $payload, string $key): ?CarbonImmutable
    {
        $value = self::nullableString($payload, $key);

        return $value === null ? null : CarbonImmutable::parse($value);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private static function topics(array $payload): array
    {
        if (! array_key_exists('topics', $payload) || $payload['topics'] === null) {
            return [];
        }

        if (! is_array($payload['topics']) || ! array_is_list($payload['topics'])) {
            throw new InvalidArgumentException('The GitHub response contained an invalid topics value.');
        }

        foreach ($payload['topics'] as $topic) {
            if (! is_string($topic)) {
                throw new InvalidArgumentException('The GitHub response contained an invalid topic.');
            }
        }

        return $payload['topics'];
    }
}
