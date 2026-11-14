<?php

namespace App\Exceptions;

use RuntimeException;

class WorkflowException extends RuntimeException
{
    public static function noActiveTemplate(string $requestType): self
    {
        return new self("No active workflow is configured for request type \"{$requestType}\".");
    }

    public static function noEligibleApprover(string $stage): self
    {
        return new self("Stage \"{$stage}\" has no active approver. The request was not submitted.");
    }

    public static function invalidTransition(string $message): self
    {
        return new self($message);
    }
}
