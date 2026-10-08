import './passkeys';

document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[data-keep-period]') : null;

    if (! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }

    const current = new URL(window.location.href);
    const target = new URL(link.href, window.location.origin);

    for (const key of ['month', 'year']) {
        const value = current.searchParams.get(key);

        if (value) {
            target.searchParams.set(key, value);
        } else {
            target.searchParams.delete(key);
        }
    }

    link.href = `${target.pathname}${target.search}${target.hash}`;
}, true);
