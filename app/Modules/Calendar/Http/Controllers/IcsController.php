<?php

namespace App\Modules\Calendar\Http\Controllers;

use App\Models\User;
use App\Modules\Calendar\Services\CalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class IcsController extends Controller
{
    public function download(Request $request, CalendarService $calendar): Response
    {
        $user = $request->user();
        $events = $calendar->eventsFor($user, now()->subMonth(), now()->addYear());
        $ics = $calendar->toIcs($events);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="oj-intranet.ics"',
        ]);
    }

    public function token(Request $request, CalendarService $calendar): RedirectResponse
    {
        $token = $calendar->ensureIcsToken($request->user());

        return back()->with('status', 'Personal ICS feed: '.route('calendar.ics.feed', $token));
    }

    public function feed(string $token, CalendarService $calendar): Response
    {
        $user = User::query()->where('ics_token', $token)->where('is_active', true)->firstOrFail();
        $events = $calendar->eventsFor($user, now()->subMonth(), now()->addYear());
        $ics = $calendar->toIcs($events, 'OJ Intranet — '.$user->name);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
