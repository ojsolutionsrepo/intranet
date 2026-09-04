<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Shared\Services\AuditLogger;
use App\Shared\Services\Branding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Classic multipart uploads for branding assets.
 * Livewire WithFileUploads needs PHP tmpfile()/fileinfo — often disabled on shared hosts.
 */
class BrandingUploadController extends Controller
{
    public function uploadLogo(Request $request, Branding $branding, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'file', 'max:2048', 'extensions:jpg,jpeg,png,gif,webp,svg'],
        ]);

        $before = ['site_logo' => $branding->logoPath()];

        try {
            $path = $branding->storeLogo($request->file('logo'));
        } catch (\Throwable $e) {
            return back()->withErrors(['logo' => $e->getMessage()]);
        }

        $audit->log('settings.logo_updated', null, $before, ['site_logo' => $path]);

        return back()->with('status', 'Site logo updated.');
    }

    public function uploadFavicon(Request $request, Branding $branding, AuditLogger $audit): RedirectResponse
    {
        $request->validate([
            'favicon' => ['required', 'file', 'max:512', 'extensions:ico,png,jpg,jpeg,gif,svg,webp'],
        ]);

        $before = ['site_favicon' => $branding->faviconPath()];

        try {
            $path = $branding->storeFavicon($request->file('favicon'));
        } catch (\Throwable $e) {
            return back()->withErrors(['favicon' => $e->getMessage()]);
        }

        $audit->log('settings.favicon_updated', null, $before, ['site_favicon' => $path]);

        return back()->with('status', 'Favicon updated.');
    }

    public function removeLogo(Branding $branding, AuditLogger $audit): RedirectResponse
    {
        $before = ['site_logo' => $branding->logoPath()];
        $branding->clearLogo();
        $audit->log('settings.logo_removed', null, $before, ['site_logo' => null]);

        return back()->with('status', 'Site logo removed.');
    }

    public function removeFavicon(Branding $branding, AuditLogger $audit): RedirectResponse
    {
        $before = ['site_favicon' => $branding->faviconPath()];
        $branding->clearFavicon();
        $audit->log('settings.favicon_removed', null, $before, ['site_favicon' => null]);

        return back()->with('status', 'Favicon removed.');
    }
}
