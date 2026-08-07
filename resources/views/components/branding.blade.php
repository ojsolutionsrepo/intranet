@php
    $branding = app(\App\Shared\Services\Branding::class);
    $faviconUrl = $branding->faviconUrl();
@endphp
@if ($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}">
@endif
{!! $branding->accentStyleTag() !!}
