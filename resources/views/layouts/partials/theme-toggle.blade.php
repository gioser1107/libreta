<button
    type="button"
    x-data="{ dark: document.documentElement.classList.contains('dark') }"
    @lb-theme.window="dark = !!$event.detail"
    @click="window.lbSetTheme(!dark, $event)"
    class="lb-menu-item"
>
    <svg x-show="!dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
    </svg>
    <svg x-show="dark" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 3v1m0 16v1m8.66-13.66l-.7.7M4.04 19.96l-.7.7M21 12h-1M4 12H3m16.66 7.66l-.7-.7M4.04 4.04l-.7-.7M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
    </svg>
    <span x-text="dark ? 'Tema claro' : 'Tema oscuro'"></span>
</button>
