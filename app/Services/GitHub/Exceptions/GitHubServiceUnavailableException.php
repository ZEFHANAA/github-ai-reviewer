<?php

namespace App\Services\GitHub\Exceptions;

class GitHubServiceUnavailableException extends GitHubRepositoryException
{
    public function __construct()
    {
        parent::__construct('GitHub is temporarily unavailable. Please try again shortly.');
    }

    public function status(): int
    {
        return 503;
    }
}
