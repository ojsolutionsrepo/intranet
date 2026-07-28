<?php

namespace App\Modules\Dashboard\Services;

use App\Models\User;
use App\Modules\Dashboard\Models\UserDashboardPref;
use Illuminate\Support\Collection;

final class WidgetRegistry
{
    /**
     * @return array<string, array{label: string, component: string, permission: string|null, order: int}>
     */
    public function definitions(): array
    {
        return [
            'announcements' => [
                'label' => 'Announcements',
                'component' => 'dashboard.widgets.announcements',
                'permission' => 'news.view',
                'order' => 10,
            ],
            'my_documents' => [
                'label' => 'My Documents',
                'component' => 'dashboard.widgets.my-documents',
                'permission' => 'documents.view',
                'order' => 20,
            ],
            'upcoming_events' => [
                'label' => 'Upcoming Events',
                'component' => 'dashboard.widgets.upcoming-events',
                'permission' => 'calendar.view',
                'order' => 30,
            ],
            'acknowledgements' => [
                'label' => 'Outstanding acknowledgements',
                'component' => 'dashboard.widgets.acknowledgements',
                'permission' => 'policies.view',
                'order' => 40,
            ],
            'quick_links' => [
                'label' => 'Quick Links',
                'component' => 'dashboard.widgets.quick-links',
                'permission' => null,
                'order' => 50,
            ],
            'my_projects' => [
                'label' => 'My Projects',
                'component' => 'dashboard.widgets.my-projects',
                'permission' => 'projects.view',
                'order' => 60,
            ],
            'new_joiners' => [
                'label' => 'New joiners',
                'component' => 'dashboard.widgets.new-joiners',
                'permission' => 'directory.view',
                'order' => 70,
            ],
        ];
    }

    /**
     * @return Collection<int, array{key: string, label: string, component: string}>
     */
    public function forUser(User $user): Collection
    {
        $defs = $this->definitions();
        $pref = UserDashboardPref::query()->where('user_id', $user->id)->first();
        $order = $pref?->widgets ?? array_keys(collect($defs)->sortBy('order')->all());

        return collect($order)
            ->filter(fn (string $key) => isset($defs[$key]))
            ->map(function (string $key) use ($defs, $user) {
                $def = $defs[$key];
                if ($def['permission'] && ! $user->can($def['permission'])) {
                    return null;
                }

                return [
                    'key' => $key,
                    'label' => $def['label'],
                    'component' => $def['component'],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  list<string>  $widgetKeys
     */
    public function savePrefs(User $user, array $widgetKeys): void
    {
        $valid = array_values(array_filter($widgetKeys, fn ($k) => isset($this->definitions()[$k])));

        UserDashboardPref::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['widgets' => $valid],
        );
    }
}
