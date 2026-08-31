<?php

namespace App\Shared\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Site branding (UR-ADM-05) — logo, favicon, accent colour overrides.
 */
final class Branding
{
    public const DEFAULT_ACCENT = '#d97b22';

    public function __construct(private readonly Settings $settings) {}

    public function accentColor(): string
    {
        $color = (string) $this->settings->get('brand_color', self::DEFAULT_ACCENT);

        return $this->isValidHex($color) ? strtolower($color) : self::DEFAULT_ACCENT;
    }

    public function logoPath(): ?string
    {
        $path = $this->settings->get('site_logo');

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function faviconPath(): ?string
    {
        $path = $this->settings->get('site_favicon');

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function logoUrl(): ?string
    {
        return $this->publicUrl($this->logoPath());
    }

    public function faviconUrl(): ?string
    {
        return $this->publicUrl($this->faviconPath());
    }

    public function storeLogo(UploadedFile $file): string
    {
        $this->deleteStored($this->logoPath());
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        if ($ext === '' || strlen($ext) > 5) {
            $ext = 'png';
        }

        $path = $this->storePublicUpload($file, 'branding/logo.'.$ext);

        $this->settings->set('site_logo', $path, 'branding');

        return $path;
    }

    public function storeFavicon(UploadedFile $file): string
    {
        $this->deleteStored($this->faviconPath());
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        if ($ext === '' || strlen($ext) > 5) {
            $ext = 'png';
        }

        $path = $this->storePublicUpload($file, 'branding/favicon.'.$ext);

        $this->settings->set('site_favicon', $path, 'branding');

        return $path;
    }

    public function clearLogo(): void
    {
        $this->deleteStored($this->logoPath());
        $this->settings->set('site_logo', null, 'branding');
    }

    public function clearFavicon(): void
    {
        $this->deleteStored($this->faviconPath());
        $this->settings->set('site_favicon', null, 'branding');
    }

    /**
     * CSS custom properties for a given accent (used for live admin preview).
     */
    public function accentCssFor(string $hex): string
    {
        $base = $this->isValidHex($hex) ? strtolower($hex) : self::DEFAULT_ACCENT;
        $dark = $this->adjustBrightness($base, -0.18);
        $light = $this->adjustBrightness($base, 0.22);
        $soft = $this->hexToRgba($base, 0.2);

        return implode('', [
            "--sig-600: {$dark} !important;",
            "--sig-500: {$base} !important;",
            "--sig-400: {$light} !important;",
            "--sig-100: {$soft} !important;",
            "--signal-glow: 0 0 14px {$this->hexToRgba($base, 0.55)}, 0 0 36px {$this->hexToRgba($base, 0.28)} !important;",
            "--signal-glow-soft: 0 0 24px {$this->hexToRgba($base, 0.18)} !important;",
            "--atmosphere-3: {$this->hexToRgba($base, 0.12)} !important;",
        ]);
    }

    /**
     * CSS custom properties for the accent (signal) scale.
     */
    public function accentCss(): string
    {
        return $this->accentCssFor($this->accentColor());
    }

    /**
     * Full style element overriding accent tokens across themes.
     * Built in PHP so Blade templates avoid embedding CSS that confuses the IDE linter.
     */
    public function accentStyleTag(): string
    {
        // Match app.css theme selectors (incl. system+resolved) so overrides win on specificity.
        $selector = implode(', ', [
            ':root',
            'html[data-theme="dark"]',
            'html[data-theme="light"]',
            'html[data-theme="system"]',
            'html[data-theme="system"][data-theme-resolved="dark"]',
            'html[data-theme="system"][data-theme-resolved="light"]',
        ]);

        return '<style id="oj-brand-accent">'.$selector.'{'.$this->accentCss().'}</style>';
    }

    public function isValidHex(string $color): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $color);
    }

    private function publicDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk;
    }

    private function publicUrl(?string $path): ?string
    {
        if (! $path || ! $this->publicDisk()->exists($path)) {
            return null;
        }

        // Cache-bust so browsers pick up replaced logo/favicon files with the same name.
        return $this->publicDisk()->url($path).'?v='.$this->publicDisk()->lastModified($path);
    }

    private function deleteStored(?string $path): void
    {
        if ($path && $this->publicDisk()->exists($path)) {
            $this->publicDisk()->delete($path);
        }
    }

    /**
     * Persist an upload to the public disk, throwing if the write fails
     * (the public disk has throw=false by default, which silently drops logos).
     */
    private function storePublicUpload(UploadedFile $file, string $path): string
    {
        $disk = $this->publicDisk();
        $disk->makeDirectory(dirname($path));

        $real = $file->getRealPath();
        if (! is_string($real) || $real === '' || ! is_readable($real)) {
            throw new \RuntimeException('Uploaded file is missing or unreadable. Try selecting it again.');
        }

        $contents = file_get_contents($real);
        if ($contents === false || $contents === '') {
            throw new \RuntimeException('Uploaded file is empty. Try selecting it again.');
        }

        if (! $disk->put($path, $contents)) {
            throw new \RuntimeException('Could not write branding file to public storage.');
        }

        if (! $disk->exists($path)) {
            throw new \RuntimeException('Branding file was not found after write.');
        }

        return $path;
    }

    private function adjustBrightness(string $hex, float $percent): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = (int) max(0, min(255, $r + (255 * $percent)));
        $g = (int) max(0, min(255, $g + (255 * $percent)));
        $b = (int) max(0, min(255, $b + (255 * $percent)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }
}
