const MIN_SCALE = 0.8;
const MAX_SCALE = 1.5;
const STEP = 0.1;

export function useAccessibility() {
    const aplicar = () => {
        const fontScale = parseFloat(localStorage.getItem('fontScale') || '1');
        const altoContraste = localStorage.getItem('altoContraste') === 'true';
        document.documentElement.style.setProperty('--font-scale', String(fontScale));
        document.documentElement.classList.toggle('alto-contraste', altoContraste);
    };

    const aumentarLetra = () => {
        const current = parseFloat(localStorage.getItem('fontScale') || '1');
        const next = Math.min(MAX_SCALE, +(current + STEP).toFixed(1));
        localStorage.setItem('fontScale', String(next));
        aplicar();
        return next;
    };

    const reducirLetra = () => {
        const current = parseFloat(localStorage.getItem('fontScale') || '1');
        const next = Math.max(MIN_SCALE, +(current - STEP).toFixed(1));
        localStorage.setItem('fontScale', String(next));
        aplicar();
        return next;
    };

    const toggleContraste = () => {
        const next = localStorage.getItem('altoContraste') !== 'true';
        localStorage.setItem('altoContraste', String(next));
        aplicar();
        return next;
    };

    const getFontScale = () => parseFloat(localStorage.getItem('fontScale') || '1');
    const isAltoContraste = () => localStorage.getItem('altoContraste') === 'true';

    return {
        aplicar,
        aumentarLetra,
        reducirLetra,
        toggleContraste,
        getFontScale,
        isAltoContraste,
    };
}

/** Restaura preferencias antes del primer render (evita parpadeo). */
export function initAccessibilityEarly() {
    const { aplicar } = useAccessibility();
    aplicar();
}
