<?php

namespace App\Enums;

enum ServerStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Maintenance => 'Maintenance',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Online => 'emerald',
            self::Offline => 'red',
            self::Maintenance => 'amber',
        };
    }
}
