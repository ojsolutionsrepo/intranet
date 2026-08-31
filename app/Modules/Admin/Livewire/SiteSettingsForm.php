<?php

namespace App\Modules\Admin\Livewire;

use App\Shared\Services\AuditLogger;
use App\Shared\Services\Branding;
use App\Shared\Services\Settings;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class SiteSettingsForm extends Component
{
    use WithFileUploads;

    public string $site_name = '';

    public int $session_idle_timeout = 480;

    public string $privacy_contact = '';

    public string $brand_color = Branding::DEFAULT_ACCENT;

    public ?TemporaryUploadedFile $logo = null;

    public ?TemporaryUploadedFile $favicon = null;

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
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,gif,svg,webp', 'max:512'],
        ]);

        $before = [
            'site_name' => $settings->get('site_name'),
            'session_idle_timeout' => $settings->get('session_idle_timeout'),
            'privacy_contact' => $settings->get('privacy_contact'),
            'brand_color' => $settings->get('brand_color'),
            'site_logo' => $settings->get('site_logo'),
            'site_favicon' => $settings->get('site_favicon'),
        ];

        $settings->set('site_name', $this->site_name, 'branding');
        $settings->set('session_idle_timeout', $this->session_idle_timeout, 'auth');
        $settings->set('privacy_contact', $this->privacy_contact, 'gdpr');
        $settings->set('brand_color', strtolower($this->brand_color), 'branding');

        try {
            if ($this->logo) {
                $branding->storeLogo($this->logo);
                $this->logo = null;
            }

            if ($this->favicon) {
                $branding->storeFavicon($this->favicon);
                $this->favicon = null;
            }
        } catch (\Throwable $e) {
            $this->addError('logo', $e->getMessage());

            return;
        }

        $this->logoUrl = $branding->logoUrl();
        $this->faviconUrl = $branding->faviconUrl();

        $audit->log('settings.updated', null, $before, [
            'site_name' => $this->site_name,
            'session_idle_timeout' => $this->session_idle_timeout,
            'privacy_contact' => $this->privacy_contact,
            'brand_color' => strtolower($this->brand_color),
            'site_logo' => $settings->get('site_logo'),
            'site_favicon' => $settings->get('site_favicon'),
        ]);

        session()->flash('status', 'Site settings saved.');
        // Full redirect so layout (sidebar name, logo, accent CSS) reloads with new values.
        $this->redirect(route('admin.settings'), navigate: false);
    }

    public function removeLogo(Branding $branding, AuditLogger $audit): void
    {
        $before = ['site_logo' => $branding->logoPath()];
        $branding->clearLogo();
        $this->logoUrl = null;
        $audit->log('settings.logo_removed', null, $before, ['site_logo' => null]);
        session()->flash('status', 'Site logo removed.');
        $this->redirect(route('admin.settings'), navigate: false);
    }

    public function removeFavicon(Branding $branding, AuditLogger $audit): void
    {
        $before = ['site_favicon' => $branding->faviconPath()];
        $branding->clearFavicon();
        $this->faviconUrl = null;
        $audit->log('settings.favicon_removed', null, $before, ['site_favicon' => null]);
        session()->flash('status', 'Favicon removed.');
        $this->redirect(route('admin.settings'), navigate: false);
    }

    public function render()
    {
        return view('admin-module::livewire.site-settings');
    }
}
