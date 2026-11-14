<?php

namespace App\Enums;

enum ApproverScope: string
{
    case RequesterDepartment = 'requester_department';
    case Global = 'global';
    case SpecificUser = 'specific_user';

    public function label(): string
    {
        return match ($this) {
            self::RequesterDepartment => 'Requester department',
            self::Global => 'Any department',
            self::SpecificUser => 'Specific user',
        };
    }
}
