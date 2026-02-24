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
    case StartCustomFlow = 'start_custom_flow';
    case SaveCustomAnswer = 'save_custom_answer';
    case CancelPipeline = 'cancel_pipeline';
    case EscalateToHuman = 'escalate_to_human';
}
