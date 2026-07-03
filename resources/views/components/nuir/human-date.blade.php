@props(['date', 'fallback' => '—'])
@php
    $carbon = $date instanceof \Illuminate\Support\Carbon
        ? $date
        : ($date ? \Illuminate\Support\Carbon::parse($date) : null);
@endphp
@if ($carbon)
    <span title="{{ $carbon->translatedFormat('d M Y H:i') }}">{{ $carbon->diffForHumans() }}</span>
@else
    <span>{{ $fallback }}</span>
@endif
