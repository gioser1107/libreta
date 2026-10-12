@props(['href' => null])

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->class('lb-back') }}>
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>{{ $slot->isNotEmpty() ? $slot : 'Atrás' }}</span>
    </a>
@else
    <button type="button" {{ $attributes->class('lb-back') }}>
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>{{ $slot->isNotEmpty() ? $slot : 'Atrás' }}</span>
    </button>
@endif
