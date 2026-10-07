<script>
    (function () {
        var storageKey = 'lb-theme';

        try {
            if (sessionStorage.getItem('lb-booted') === '1') {
                document.documentElement.classList.add('lb-booted');
            } else {
                sessionStorage.setItem('lb-booted', '1');
            }
        } catch (e) {}

        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
            document.documentElement.classList.add('lb-standalone');
        }

        function persistTheme(dark) {
            var value = dark ? 'dark' : 'light';
            try {
                localStorage.setItem(storageKey, value);
            } catch (e) {}
            try {
                var secure = location.protocol === 'https:' ? ';Secure' : '';
                document.cookie = storageKey + '=' + value + ';path=/;max-age=31536000;SameSite=Lax' + secure;
            } catch (e) {}
        }

        window.lbApplyTheme = function () {
            var dark = false;
            try {
                dark = localStorage.getItem(storageKey) === 'dark';
            } catch (e) {}
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
            var themeMeta = document.querySelector('meta[name="theme-color"]');
            if (themeMeta) {
                themeMeta.setAttribute('content', dark ? '#14171c' : '#f3f4f6');
            }
            persistTheme(dark);
            window.dispatchEvent(new CustomEvent('lb-theme', { detail: dark }));
        };

        window.lbSetTheme = function (on) {
            persistTheme(!!on);
            window.lbApplyTheme();
        };

        window.lbApplyTheme();
        if (!window.__lbThemeBoot) {
            window.__lbThemeBoot = true;
            document.addEventListener('livewire:navigated', window.lbApplyTheme);
            document.addEventListener('alpine:navigating', function (event) {
                if (!event.detail || typeof event.detail.onSwap !== 'function') {
                    return;
                }
                event.detail.onSwap(window.lbApplyTheme);
            });
        }
    })();
</script>
