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

        function isStandalone() {
            return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        }

        function restoreDocumentChrome() {
            try {
                if (sessionStorage.getItem('lb-booted') === '1') {
                    document.documentElement.classList.add('lb-booted');
                }
            } catch (e) {}

            if (isStandalone()) {
                document.documentElement.classList.add('lb-standalone');
            }
        }

        if (isStandalone()) {
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

        window.lbSetTheme = function (on, event) {
            var next = !!on;
            var root = document.documentElement;
            var reduce = false;

            try {
                reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            } catch (e) {}

            function apply() {
                persistTheme(next);
                window.lbApplyTheme();
            }

            if (reduce || root.classList.contains('lb-theme-vt')) {
                if (!root.classList.contains('lb-theme-vt')) {
                    apply();
                }

                return;
            }

            if (typeof document.startViewTransition !== 'function') {
                root.classList.add('lb-theme-fade');
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(function () {
                        apply();
                        window.setTimeout(function () {
                            root.classList.remove('lb-theme-fade');
                        }, 480);
                    });
                });

                return;
            }

            var x = window.innerWidth / 2;
            var y = window.innerHeight / 2;

            if (event && typeof event.clientX === 'number' && typeof event.clientY === 'number') {
                x = event.clientX;
                y = event.clientY;
            }

            var radius = Math.hypot(
                Math.max(x, window.innerWidth - x),
                Math.max(y, window.innerHeight - y)
            );

            root.style.setProperty('--lb-theme-x', x + 'px');
            root.style.setProperty('--lb-theme-y', y + 'px');
            root.style.setProperty('--lb-theme-r', radius + 'px');
            root.classList.add('lb-theme-vt');

            var transition;

            try {
                transition = document.startViewTransition(apply);
            } catch (e) {
                root.classList.remove('lb-theme-vt');
                apply();

                return;
            }

            var clear = function () {
                root.classList.remove('lb-theme-vt');
            };

            transition.finished.then(clear, clear);
        };

        window.lbApplyTheme();
        if (!window.__lbThemeBoot) {
            window.__lbThemeBoot = true;
            document.addEventListener('livewire:navigated', window.lbApplyTheme);
            document.addEventListener('alpine:navigating', function (event) {
                if (!event.detail || typeof event.detail.onSwap !== 'function') {
                    return;
                }
                event.detail.onSwap(function () {
                    restoreDocumentChrome();
                    window.lbApplyTheme();
                });
            });
        }
    })();
</script>
