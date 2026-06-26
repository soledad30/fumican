import { ref } from 'vue';

const THEME_CLASSES = [
    'theme-ninos-day', 'theme-ninos-night',
    'theme-jovenes-day', 'theme-jovenes-night',
    'theme-adultos-day', 'theme-adultos-night',
];

const getTimeVariant = () => {
    const hour = new Date().getHours();
    return (hour >= 6 && hour < 19) ? 'day' : 'night';
};

export function applyThemeEarly() {
    const baseTheme = localStorage.getItem('theme') || 'adultos';
    const variant = getTimeVariant();
    document.documentElement.classList.remove(...THEME_CLASSES);
    document.documentElement.classList.add(`theme-${baseTheme}-${variant}`);
    document.documentElement.setAttribute('data-chrome-mode', variant);
    document.documentElement.setAttribute('data-theme-mode', 'light');
}

export function useTheme() {
    const theme = ref(localStorage.getItem('theme') || 'adultos');

    const applyThemeClasses = (baseTheme) => {
        const variant = getTimeVariant();
        document.documentElement.classList.remove(...THEME_CLASSES);
        document.documentElement.classList.add(`theme-${baseTheme}-${variant}`);
        document.documentElement.setAttribute('data-chrome-mode', variant);
        // Contenido admin siempre en modo claro → texto oscuro legible
        document.documentElement.setAttribute('data-theme-mode', 'light');
    };

    const setTheme = (newTheme) => {
        theme.value = newTheme;
        localStorage.setItem('theme', newTheme);
        applyThemeClasses(newTheme);
    };

    const initTheme = () => {
        applyThemeClasses(theme.value);
        // Re-evaluar día/noche cada 30 minutos
        setInterval(() => applyThemeClasses(theme.value), 30 * 60 * 1000);
    };

    return {
        theme,
        setTheme,
        initTheme,
        getTimeVariant,
    };
}
