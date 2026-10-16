<?php

namespace DA\Inventory\Enums;

enum DashboardTrend: string
{
    case Up = 'up';
    case Down = 'down';
    case Unchanged = 'unchanged';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
