<?php

namespace App\Services\GitHub\Exceptions;

class GitHubUnexpectedResponseException extends GitHubRepositoryException
{
    public function __construct()
    {
        parent::__construct('GitHub returned an unexpected repository response. Please try again later.');
    }

    public function status(): int
    {
        return 502;
    }
}
