<button
    type="button"
    role="switch"
    @class([
        'lb-row' => ($variant ?? 'menu') === 'row',
        'lb-menu-item' => ($variant ?? 'menu') === 'menu',
    ])
    x-data="{ dark: document.documentElement.classList.contains('dark') }"
    @lb-theme.window="dark = !!$event.detail"
    @click="window.lbSetTheme(!dark, $event)"
    :aria-checked="dark"
    aria-checked="{{ request()->cookie('lb-theme') === 'dark' ? 'true' : 'false' }}"
>
    <span>Modo oscuro</span>
    <span @class(['lb-switch', 'is-sm' => ($variant ?? 'menu') === 'menu']) aria-hidden="true">
        <span class="lb-switch-knob"></span>
    </span>
</button>
