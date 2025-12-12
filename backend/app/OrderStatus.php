<?php

namespace App;

enum OrderStatus: int
{
    case OPEN = 1;
    case FILLED = 2;
    case CANCELLED = 3;

    public function label(): string
    {
        return match($this) {
            OrderStatus::OPEN => 'Open',
            OrderStatus::FILLED => 'Filled',
            OrderStatus::CANCELLED => 'Cancelled',
        };
    }

}
