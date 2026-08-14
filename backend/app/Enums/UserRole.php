<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Manager = 'manager';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Manager => 'Manager',
            self::Member => 'Member',
        };
    }
}
