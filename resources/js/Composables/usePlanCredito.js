import { ref, computed, watch } from "vue";

function fechaHoyLocal() {
    return new Date().toISOString().slice(0, 16);
}

function fechaSugerida(dias) {
    const d = new Date();
    d.setDate(d.getDate() + dias);
    return d.toISOString().slice(0, 10);
}

function maxFechaVencimiento() {
    const d = new Date();
    d.setMonth(d.getMonth() + 1);
    return d.toISOString().slice(0, 10);
}

export function usePlanCredito(getSaldo) {
    const numPagos = ref(2);
    const pagosPlan = ref([]);

    function rebuildPlan() {
        const saldo = Number(getSaldo()) || 0;
        const n = Math.max(2, Math.min(12, Number(numPagos.value) || 2));
        numPagos.value = n;

        const previo = pagosPlan.value;
        const hoy = fechaHoyLocal();
        const maxFecha = maxFechaVencimiento();

        pagosPlan.value = Array.from({ length: n }, (_, i) => {
            const existente = previo[i];
            let fecha = existente?.fecha;
            if (!fecha) {
                fecha = i === 0 ? hoy : fechaSugerida(i * 15);
            }
            if (i > 0 && fecha > maxFecha) {
                fecha = maxFecha;
            }

            return {
                monto: existente?.monto ?? (i === 0 ? saldo : ""),
                fecha,
                esInicial: i === 0,
                etiqueta: i === 0 ? "Inicial (cobrar hoy)" : `Cuota ${i}`,
            };
        });
    }

    function initPlan() {
        numPagos.value = 2;
        pagosPlan.value = [];
        rebuildPlan();
    }

    watch(numPagos, () => rebuildPlan());

    const sumaPlan = computed(() =>
        pagosPlan.value.reduce((s, p) => s + (Number(p.monto) || 0), 0)
    );

    const diferencia = computed(() => {
        const saldo = Number(getSaldo()) || 0;
        return Math.round((saldo - sumaPlan.value) * 100) / 100;
    });

    const planValido = computed(() => {
        if (!pagosPlan.value.length) return false;
        if (pagosPlan.value.some((p) => !p.monto || Number(p.monto) <= 0)) return false;
        return Math.abs(diferencia.value) < 0.02;
    });

    function payloadCuotasPlan() {
        return pagosPlan.value.map((p) => ({
            monto: Number(p.monto),
            fecha: p.esInicial ? p.fecha : `${p.fecha}T12:00`,
        }));
    }

    return {
        numPagos,
        pagosPlan,
        initPlan,
        rebuildPlan,
        sumaPlan,
        diferencia,
        planValido,
        maxFechaVencimiento,
        payloadCuotasPlan,
    };
}
