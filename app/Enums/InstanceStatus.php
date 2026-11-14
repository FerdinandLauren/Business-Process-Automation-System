<?php

namespace App\Enums;

enum InstanceStatus: string
{
    case Running = 'running';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
