import { customRef } from 'vue';

export function useDebouncedRef(initialValue, delay = 200, immediate = false) {
  return customRef((track, trigger) => {
    let timeout;
    // El valor interno se maneja por separado
    let value = initialValue;

    const debouncedWatcher = (newValue) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        value = newValue; // El valor solo se actualiza aquí, después del delay
        trigger();      // Y se notifica a la vista
      }, delay);
    };

    // ... código del flag 'immediate'

    return {
      get() {
        track();
        return value;
      },
      set(newValue) {
        // Solo reinicia el temporizador. No cambia el valor inmediatamente.
        debouncedWatcher(newValue);
      },
    };
  });
}
