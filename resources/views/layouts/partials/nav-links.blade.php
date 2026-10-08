@php
    $month = request()->integer('month');
    $year = request()->integer('year');
    $period = ($month >= 1 && $month <= 12 && $year >= 2020 && $year <= 2100)
        ? ['month' => $month, 'year' => $year]
        : [];
    $links = [
        [
            'route' => 'dashboard',
            'label' => 'Resumen',
            'active' => request()->routeIs('dashboard'),
            'can' => auth()->user()->can('ingresos.view') && auth()->user()->can('egresos.view'),
            'icon' => 'M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z',
        ],
        [
            'route' => 'incomes.index',
            'label' => 'Ingresos',
            'active' => request()->routeIs('incomes.index'),
            'can' => auth()->user()->can('ingresos.view'),
            'icon' => 'M12 19V5m0 0l-5 5m5-5l5 5',
        ],
        [
            'route' => 'expenses.index',
            'label' => 'Egresos',
            'active' => request()->routeIs('expenses.index'),
            'can' => auth()->user()->can('egresos.view'),
            'icon' => 'M12 5v14m0 0l-5-5m5 5l5-5',
        ],
        [
            'route' => 'calculator',
            'label' => 'Calculadora',
            'active' => request()->routeIs('calculator'),
            'can' => true,
            'icon' => 'M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2zm0 4h12M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01',
        ],
    ];
@endphp

@foreach($links as $link)
    @if($link['can'])
        <a
            wire:navigate
            data-keep-period
            href="{{ route($link['route'], $period) }}"
            title="{{ $link['label'] }}"
            @class(['is-active' => $link['active']])
            @if($link['active']) aria-current="page" @endif
        >
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/></svg>
            <span class="lb-lockup-text">{{ $link['label'] }}</span>
        </a>
    @endif
@endforeach
