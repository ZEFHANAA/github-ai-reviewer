<?php

namespace App\ValueObjects;

final readonly class RepositorySnapshot
{
    /** @param list<string> $paths @param list<string> $unavailableData @param list<string> $omittedData */
    public function __construct(public GitHubRepositoryMetadata $metadata, public array $paths, public array $unavailableData = [], public array $omittedData = []) {}

    public function has(string $path): bool
    {
        return in_array(strtolower($path), array_map(strtolower(...), $this->paths), true);
    }

    public function starts(string $prefix): bool
    {
        foreach ($this->paths as $path) {
            if (str_starts_with(strtolower($path), strtolower($prefix))) {
                return true;
            }
        }

        return false;
    }

    public function isOmitted(string $key): bool
    {
        foreach ($this->omittedData as $item) {
            if (str_starts_with($item, $key) || $item === $key) {
                return true;
            }
        }

        return false;
    }
}
