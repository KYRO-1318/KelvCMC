<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnually = 'semi_annually';
    case Annually = 'annually';
    case OneTime = 'onetime';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::SemiAnnually => 'Semi-annually',
            self::Annually => 'Annually',
            self::OneTime => 'One time',
        };
    }

    /** Number of months covered by this cycle. */
    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::SemiAnnually => 6,
            self::Annually => 12,
            self::OneTime => 0,
        };
    }
}
