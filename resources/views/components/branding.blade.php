@php
    $branding = app(\App\Shared\Services\Branding::class);
    $faviconUrl = $branding->faviconUrl();
    $faviconType = $branding->faviconMime();
@endphp
@if ($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}"@if ($faviconType) type="{{ $faviconType }}"@endif sizes="any">
    <link rel="shortcut icon" href="{{ $faviconUrl }}"@if ($faviconType) type="{{ $faviconType }}"@endif>
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
@endif
{!! $branding->accentStyleTag() !!}
