<?php

namespace App\Services\GitHub\Exceptions;

class GitHubRepositoryNotFoundException extends GitHubRepositoryException
{
    public function __construct()
    {
        parent::__construct('We could not find that public GitHub repository. It may not exist or may be inaccessible.');
    }

    public function status(): int
    {
        return 404;
    }
}
