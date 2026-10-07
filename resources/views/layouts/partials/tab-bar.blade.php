@php
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
                href="{{ route($tab['route']) }}"
                @class(['is-active' => $tab['active']])
                @if($tab['active']) aria-current="page" @endif
            >
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}"/>
                </svg>
                {{ $tab['label'] }}
            </a>
        @endif
    @endforeach
</nav>
