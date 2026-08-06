<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case CustomerReply = 'customer_reply';
    case OnHold = 'on_hold';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Answered => 'Answered',
            self::CustomerReply => 'Customer replied',
            self::OnHold => 'On hold',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'sky',
            self::Answered => 'emerald',
            self::CustomerReply => 'amber',
            self::OnHold => 'gray',
            self::Closed => 'slate',
        };
    }
}
