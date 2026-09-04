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

    /**
     * MIME type for the stored favicon, when known from the file extension.
     */
    public function faviconMime(): ?string
    {
        $path = $this->faviconPath();
        if (! $path) {
            return null;
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => null,
        };
    }

    public function storeLogo(UploadedFile $file): string
    {
        $ext = $this->assertSafeBrandingUpload($file, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], 2048);
        $this->deleteStored($this->logoPath());
        $path = $this->storePublicUpload($file, 'branding/logo.'.$ext);
        $this->settings->set('site_logo', $path, 'branding');

        return $path;
    }

    public function storeFavicon(UploadedFile $file): string
    {
        $ext = $this->assertSafeBrandingUpload($file, ['ico', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'], 512);
        $this->deleteStored($this->faviconPath());
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
     * CSS custom properties for a given accent.
     * $surface: light|dark — dark surfaces lift near-black brand colours so
     * buttons/sidebar marks stay visible (smart theme pairing).
     */
    public function accentCssFor(string $hex, string $surface = 'light'): string
    {
        $base = $this->isValidHex($hex) ? strtolower($hex) : self::DEFAULT_ACCENT;
        $base = $this->readableAccent($base, $surface);
        $dark = $this->adjustBrightness($base, -0.18);
        $light = $this->adjustBrightness($base, 0.22);
        $on = $this->relativeLuminance($base) < 0.45 ? '#f2f6fa' : '#0e1a2b';

        // Light surfaces prefer a soft solid tint (not translucent wash) and no atmosphere tint.
        if ($surface === 'light') {
            $soft = $this->mixWithWhite($base, 0.88);
            $glow = "0 0 14px {$this->hexToRgba($base, 0.35)}";
            $glowSoft = "0 0 18px {$this->hexToRgba($base, 0.12)}";
            $atmosphere = 'transparent';
        } else {
            $soft = $this->hexToRgba($base, 0.22);
            $glow = "0 0 14px {$this->hexToRgba($base, 0.55)}, 0 0 36px {$this->hexToRgba($base, 0.28)}";
            $glowSoft = "0 0 24px {$this->hexToRgba($base, 0.18)}";
            $atmosphere = $this->hexToRgba($base, 0.12);
        }

        return implode('', [
            "--sig-600: {$dark} !important;",
            "--sig-500: {$base} !important;",
            "--sig-400: {$light} !important;",
            "--sig-100: {$soft} !important;",
            "--sig-on: {$on} !important;",
            "--signal-glow: {$glow} !important;",
            "--signal-glow-soft: {$glowSoft} !important;",
            "--atmosphere-3: {$atmosphere} !important;",
        ]);
    }

    /**
     * CSS custom properties for the accent (signal) scale.
     */
    public function accentCss(): string
    {
        return $this->accentCssFor($this->accentColor(), 'dark');
    }

    /**
     * Full style element overriding accent tokens across themes.
     * Built in PHP so Blade templates avoid embedding CSS that confuses the IDE linter.
     */
    public function accentStyleTag(): string
    {
        $hex = $this->accentColor();
        $light = $this->accentCssFor($hex, 'light');
        $dark = $this->accentCssFor($hex, 'dark');

        // Dark first, then light — light selectors must win over :root when theme is light.
        // Do not target bare html[data-theme=system] (resolved attribute decides light vs dark).
        return '<style id="oj-brand-accent">'
            .':root,html[data-theme="dark"],html[data-theme="system"][data-theme-resolved="dark"]{'.$dark.'}'
            .'html[data-theme="light"],html[data-theme="system"][data-theme-resolved="light"]{'.$light.'}'
            .'</style>';
    }

    public function isValidHex(string $color): bool
    {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', $color);
    }

    /**
     * Keep brand accents usable: lift very dark colours on dark UI, deepen very
     * light colours on light UI so contrast is not washed out.
     */
    private function readableAccent(string $hex, string $surface): string
    {
        $luminance = $this->relativeLuminance($hex);

        if ($surface === 'dark' && $luminance < 0.18) {
            // Lift toward a mid/bright tint of the same hue.
            $steps = 0;
            $lifted = $hex;
            while ($this->relativeLuminance($lifted) < 0.28 && $steps < 8) {
                $lifted = $this->adjustBrightness($lifted, 0.14);
                $steps++;
            }

            return $lifted;
        }

        if ($surface === 'light' && $luminance > 0.82) {
            $steps = 0;
            $deepened = $hex;
            while ($this->relativeLuminance($deepened) > 0.55 && $steps < 8) {
                $deepened = $this->adjustBrightness($deepened, -0.12);
                $steps++;
            }

            return $deepened;
        }

        return $hex;
    }

    private function relativeLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];

        $linear = array_map(static function (float $c): float {
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, $channels);

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    /**
     * Validate branding uploads without PHP fileinfo/finfo_open (often missing on shared hosts).
     *
     * @param  list<string>  $allowedExt
     */
    private function assertSafeBrandingUpload(UploadedFile $file, array $allowedExt, int $maxKilobytes): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if ($ext === '' || ! in_array($ext, $allowedExt, true)) {
            throw new \InvalidArgumentException('Unsupported file type. Allowed: '.implode(', ', $allowedExt).'.');
        }

        $size = (int) $file->getSize();
        if ($size <= 0 || $size > $maxKilobytes * 1024) {
            throw new \InvalidArgumentException("File must be between 1 byte and {$maxKilobytes} KB.");
        }

        $real = $file->getRealPath();
        if (! is_string($real) || $real === '' || ! is_readable($real)) {
            throw new \RuntimeException('Uploaded file is missing or unreadable. Try selecting it again.');
        }

        $head = (string) file_get_contents($real, false, null, 0, 512);

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            if (@getimagesize($real) === false) {
                throw new \InvalidArgumentException('File does not look like a valid image.');
            }
        } elseif ($ext === 'svg') {
            if (! preg_match('/<svg[\s>]/i', $head) && ! str_contains(strtolower($head), '<svg')) {
                // Full file for tiny SVGs where header missed the tag.
                $full = (string) file_get_contents($real);
                if (! preg_match('/<svg[\s>]/i', $full)) {
                    throw new \InvalidArgumentException('File does not look like a valid SVG.');
                }
                if (preg_match('/<script|onload\s*=|javascript:/i', $full)) {
                    throw new \InvalidArgumentException('SVG contains disallowed script content.');
                }
            } elseif (preg_match('/<script|onload\s*=|javascript:/i', (string) file_get_contents($real))) {
                throw new \InvalidArgumentException('SVG contains disallowed script content.');
            }
        } elseif ($ext === 'ico') {
            $bytes = substr($head, 0, 4);
            $isIco = $bytes === "\x00\x00\x01\x00" || $bytes === "\x00\x00\x02\x00";
            $isPng = str_starts_with($head, "\x89PNG\r\n\x1a\n");
            if (! $isIco && ! $isPng) {
                throw new \InvalidArgumentException('File does not look like a valid ICO/PNG favicon.');
            }
        }

        return $ext;
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

        // asset() keeps subdirectory installs (e.g. /intranet) working across hosts.
        // Cache-bust so browsers pick up replaced logo/favicon files with the same name.
        return asset('storage/'.$path).'?v='.$this->publicDisk()->lastModified($path);
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

    /**
     * Mix accent toward white for soft light-theme fills (e.g. --sig-100).
     */
    private function mixWithWhite(string $hex, float $whiteAmount): string
    {
        $hex = ltrim($hex, '#');
        $amount = max(0.0, min(1.0, $whiteAmount));
        $r = (int) round(hexdec(substr($hex, 0, 2)) * (1 - $amount) + 255 * $amount);
        $g = (int) round(hexdec(substr($hex, 2, 2)) * (1 - $amount) + 255 * $amount);
        $b = (int) round(hexdec(substr($hex, 4, 2)) * (1 - $amount) + 255 * $amount);

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
