<?php

namespace App\Services\GitHub\Exceptions;

class GitHubRateLimitException extends GitHubRepositoryException
{
    public function __construct()
    {
        parent::__construct('GitHub API rate limits have been reached. Please try again later.');
    }

    public function status(): int
    {
        return 429;
    }
}
