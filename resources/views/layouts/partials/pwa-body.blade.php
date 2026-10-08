<div id="lb-splash" aria-hidden="true">
    <img src="{{ asset('icons/apple-touch-icon.png') }}" alt="">
</div>

<x-page-loader />

<div id="lb-offline" class="lb-offline" hidden>Sin conexión</div>

<div id="lb-install" class="lb-install" hidden>
    <div class="lb-install-card">
        <img src="{{ asset('icons/apple-touch-icon.png') }}" alt="" width="44" height="44">
        <div>
            <strong>Instalar {{ config('libreta.company') }}</strong>
            <p id="lb-install-text">Se abre a pantalla completa, como una app.</p>
        </div>
        <button id="lb-install-action" type="button">Instalar</button>
        <button id="lb-install-dismiss" type="button" aria-label="Ahora no">✕</button>
    </div>
    <ol id="lb-install-steps" hidden>
        <li>Toca Compartir en Safari.</li>
        <li>Elige Agregar a inicio.</li>
        <li>Pulsa Agregar y ábrela desde el icono.</li>
    </ol>
</div>

<script>
    (function () {
        var splash = document.getElementById('lb-splash');
        if (!splash) return;

        if (window.__lbSplashShown || document.documentElement.classList.contains('lb-booted')) {
            window.__lbSplashShown = true;
            document.documentElement.classList.add('lb-booted');
            splash.remove();
            return;
        }

        window.__lbSplashShown = true;

        window.setTimeout(function () {
            splash.classList.add('is-done');
            window.setTimeout(function () { splash.remove(); }, 380);
        }, 1500);
    })();
</script>
