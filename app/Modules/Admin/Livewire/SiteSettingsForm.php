<?php

namespace App\Modules\Admin\Livewire;

use App\Shared\Services\AuditLogger;
use App\Shared\Services\Settings;
use Livewire\Component;

class SiteSettingsForm extends Component
{
    public string $site_name = '';

    public int $session_idle_timeout = 480;

    public string $privacy_contact = '';

    public function mount(Settings $settings): void
    {
        $this->site_name = (string) $settings->get('site_name', config('app.name'));
        $this->session_idle_timeout = (int) $settings->get('session_idle_timeout', 480);
        $this->privacy_contact = (string) $settings->get('privacy_contact', config('gdpr.privacy_contact'));
    }

    public function save(Settings $settings, AuditLogger $audit): void
    {
        $this->validate([
            'site_name' => 'required|string|max:120',
            'session_idle_timeout' => 'required|integer|min:5|max:1440',
            'privacy_contact' => 'required|email|max:180',
        ]);

        $before = [
            'site_name' => $settings->get('site_name'),
            'session_idle_timeout' => $settings->get('session_idle_timeout'),
            'privacy_contact' => $settings->get('privacy_contact'),
        ];

        $settings->set('site_name', $this->site_name, 'branding');
        $settings->set('session_idle_timeout', $this->session_idle_timeout, 'auth');
        $settings->set('privacy_contact', $this->privacy_contact, 'gdpr');

        $audit->log('settings.updated', null, $before, [
            'site_name' => $this->site_name,
            'session_idle_timeout' => $this->session_idle_timeout,
            'privacy_contact' => $this->privacy_contact,
        ]);

        session()->flash('status', 'Site settings saved.');
    }

    public function render()
    {
        return view('admin-module::livewire.site-settings');
    }
}
