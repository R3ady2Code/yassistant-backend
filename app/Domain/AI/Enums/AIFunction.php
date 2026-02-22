<?php

declare(strict_types=1);

namespace App\Domain\AI\Enums;

enum AIFunction: string
{
    case GetBranches = 'get_branches';
    case GetServices = 'get_services';
    case GetStaff = 'get_staff';
    case GetAvailableSlots = 'get_available_slots';
    case CreateBooking = 'create_booking';
    case EscalateToHuman = 'escalate_to_human';
}
