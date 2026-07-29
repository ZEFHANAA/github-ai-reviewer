<?php

namespace App\Services\GitHub\Exceptions;

use RuntimeException;

abstract class GitHubRepositoryException extends RuntimeException
{
    abstract public function status(): int;
}
