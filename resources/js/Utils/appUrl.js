export function getAppBaseUrl() {
    const basePath = (import.meta.env.VITE_BASE_PATH || '').replace(/\/$/, '');
    if (basePath) {
        return `${window.location.origin}${basePath}`;
    }

    const fromMeta = document.querySelector('meta[name="app-url"]')?.content;
    if (fromMeta) {
        return fromMeta.replace(/\/$/, '');
    }

    const ziggyUrl = window.Ziggy?.url ?? (typeof Ziggy !== 'undefined' ? Ziggy.url : '');
    return (ziggyUrl || window.location.origin).replace(/\/$/, '');
}

export function resolveAppUrl(href) {
    if (typeof href !== 'string' || /^https?:\/\//i.test(href)) {
        return href;
    }

    if (!href.startsWith('/')) {
        return href;
    }

    const base = getAppBaseUrl();
    return base ? `${base}${href}` : href;
}
