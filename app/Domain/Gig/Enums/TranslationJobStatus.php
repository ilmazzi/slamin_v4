<?php

namespace Domain\Gig\Enums;

enum TranslationJobStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
}
