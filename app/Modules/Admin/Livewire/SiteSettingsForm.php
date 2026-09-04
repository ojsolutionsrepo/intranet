<?php

namespace App\Modules\Admin\Livewire;

use App\Shared\Services\AuditLogger;
use App\Shared\Services\Branding;
use App\Shared\Services\Settings;
use Livewire\Component;

class SiteSettingsForm extends Component
{
    public string $site_name = '';

    public int $session_idle_timeout = 480;

    public string $privacy_contact = '';

    public string $brand_color = Branding::DEFAULT_ACCENT;

    public ?string $logoUrl = null;

    public ?string $faviconUrl = null;

    public function mount(Settings $settings, Branding $branding): void
    {
        $this->site_name = (string) $settings->get('site_name', config('app.name', 'OJ Intranet'));
        $this->session_idle_timeout = (int) $settings->get('session_idle_timeout', 480);
        $this->privacy_contact = (string) $settings->get('privacy_contact', config('gdpr.privacy_contact'));
        $this->brand_color = $branding->accentColor();
        $this->logoUrl = $branding->logoUrl();
        $this->faviconUrl = $branding->faviconUrl();

        // Guard against empty required fields (e.g. null stored values) so Save is not a no-op.
        if ($this->privacy_contact === '') {
            $this->privacy_contact = (string) config('gdpr.privacy_contact', 'privacy@oj.local');
        }
        if ($this->session_idle_timeout < 5) {
            $this->session_idle_timeout = 480;
        }
    }

    public function save(Settings $settings, Branding $branding, AuditLogger $audit): void
    {
        $this->validate([
            'site_name' => 'required|string|max:120',
            'session_idle_timeout' => 'required|integer|min:5|max:1440',
            'privacy_contact' => 'required|email|max:180',
            'brand_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $before = [
            'site_name' => $settings->get('site_name'),
            'session_idle_timeout' => $settings->get('session_idle_timeout'),
            'privacy_contact' => $settings->get('privacy_contact'),
            'brand_color' => $settings->get('brand_color'),
        ];

        $settings->set('site_name', $this->site_name, 'branding');
        $settings->set('session_idle_timeout', $this->session_idle_timeout, 'auth');
        $settings->set('privacy_contact', $this->privacy_contact, 'gdpr');
        $settings->set('brand_color', strtolower($this->brand_color), 'branding');

        $this->logoUrl = $branding->logoUrl();
        $this->faviconUrl = $branding->faviconUrl();

        $audit->log('settings.updated', null, $before, [
            'site_name' => $this->site_name,
            'session_idle_timeout' => $this->session_idle_timeout,
            'privacy_contact' => $this->privacy_contact,
            'brand_color' => strtolower($this->brand_color),
        ]);

        session()->flash('status', 'Site settings saved.');
        // Full redirect so layout (sidebar name, logo, accent CSS) reloads with new values.
        $this->redirect(route('admin.settings'), navigate: false);
    }

    public function render()
    {
        return view('admin-module::livewire.site-settings');
    }
}
