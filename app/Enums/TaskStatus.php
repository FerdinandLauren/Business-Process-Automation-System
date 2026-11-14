<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RevisionRequested = 'revision_requested';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::RevisionRequested => 'Revision requested',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'caution',
            self::Approved => 'positive',
            self::Rejected => 'danger',
            self::RevisionRequested => 'caution',
            self::Cancelled => 'neutral',
        };
    }
}
