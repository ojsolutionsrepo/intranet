<?php

namespace App\Modules\Calendar\Policies;

use App\Models\User;
use App\Modules\Calendar\Models\CalendarEvent;

class CalendarEventPolicy
{
    public function view(User $user, CalendarEvent $event): bool
    {
        return $user->can('calendar.view') && $event->isVisibleTo($user);
    }

    public function manage(User $user): bool
    {
        return $user->can('calendar.manage');
    }
}
