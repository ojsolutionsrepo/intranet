<?php

namespace App\Shared\Services;

use App\Shared\Models\AnalyticsEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

final class Analytics
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function track(string $name, array $payload = [], ?int $userId = null): void
    {
        AnalyticsEvent::query()->create([
            'user_id' => $userId ?? Auth::id(),
            'name' => $name,
            'payload' => $payload,
            'ip' => Request::ip(),
        ]);
    }
}
