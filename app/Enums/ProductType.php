<?php

namespace App\Enums;

enum ProductType: string
{
    case WebHosting = 'webhosting';
    case Vps = 'vps';
    case Minecraft = 'minecraft';
    case FiveM = 'fivem';
    case Domain = 'domain';
    case License = 'license';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::WebHosting => 'Web Hosting',
            self::Vps => 'VPS',
            self::Minecraft => 'Minecraft Server',
            self::FiveM => 'FiveM Server',
            self::Domain => 'Domain',
            self::License => 'License',
            self::Custom => 'Custom Service',
        };
    }

    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }
}
