<?php

declare(strict_types=1);

namespace App\Domain\Conversation\Enums;

enum PipelinePhase: string
{
    case CustomQuestions = 'custom_questions';
    case SelectStaff = 'select_staff';
    case SelectDatetime = 'select_datetime';
    case CollectContacts = 'collect_contacts';
    case Confirm = 'confirm';
    case Complete = 'complete';
}
