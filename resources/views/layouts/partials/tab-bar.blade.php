@php
    $month = request()->integer('month');
    $year = request()->integer('year');
    $period = ($month >= 1 && $month <= 12 && $year >= 2020 && $year <= 2100)
        ? ['month' => $month, 'year' => $year]
        : [];
    $tabs = [
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
            'icon' => 'M12 19V5m0 0l-6 6m6-6l6 6',
            'tone' => 'in',
        ],
        [
            'route' => 'expenses.index',
            'label' => 'Egresos',
            'active' => request()->routeIs('expenses.index'),
            'can' => auth()->user()->can('egresos.view'),
            'icon' => 'M12 5v14m0 0l-6-6m6 6l6-6',
            'tone' => 'out',
        ],
        [
            'route' => 'banks',
            'label' => 'Bancos',
            'active' => request()->routeIs('banks'),
            'can' => true,
            'icon' => 'M4 10l8-6 8 6M6 10v8h12v-8M10 18v-4h4v4',
        ],
        [
            'route' => 'calculator',
            'label' => 'Calculadora',
            'active' => request()->routeIs('calculator'),
            'can' => true,
            'icon' => 'M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2zm0 4h12M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01',
        ],
        [
            'route' => 'profile',
            'label' => 'Cuenta',
            'active' => request()->routeIs('profile'),
            'can' => true,
            'icon' => 'M12 12a3.25 3.25 0 100-6.5 3.25 3.25 0 000 6.5zM5 19.25a7 7 0 0114 0',
        ],
    ];
@endphp

<nav class="lb-tabs" aria-label="Secciones">
    @foreach($tabs as $tab)
        @if($tab['can'])
            <a
                wire:navigate
                data-keep-period
                href="{{ route($tab['route'], $period) }}"
                @class(['is-active' => $tab['active']])
                @if($tab['active']) aria-current="page" @endif
            >
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true" @class(['is-in' => ($tab['tone'] ?? null) === 'in', 'is-out' => ($tab['tone'] ?? null) === 'out'])>
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/>
                </svg>
                {{ $tab['label'] }}
            </a>
        @endif
    @endforeach
</nav>
