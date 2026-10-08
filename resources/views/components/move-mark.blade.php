@props(['kind' => 'in'])

<span {{ $attributes->class([
    'lb-mark',
    'is-in' => $kind === 'in',
    'is-out' => $kind === 'out',
]) }} aria-hidden="true">
    @if ($kind === 'out')
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m0 0l-6-6m6 6l6-6"/>
        </svg>
    @elseif ($kind === 'move')
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m0 0l-6-6m6 6l-6 6"/>
        </svg>
    @else
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0l-6 6m6-6l6 6"/>
        </svg>
    @endif
</span>
