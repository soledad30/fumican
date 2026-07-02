import { ref, computed, watch } from "vue";
import {
    ahoraDatetimeLocal,
    fechaBoliviaDesplazada,
    maxFechaVencimientoBolivia,
} from "@/Utils/fechaBolivia";

function distribuirMontos(saldo, n) {
    const total = Math.round(Number(saldo) * 100) || 0;
    const base = Math.floor(total / n);
    const montos = [];
    let asignado = 0;

    for (let i = 0; i < n; i++) {
        if (i === n - 1) {
            montos.push((total - asignado) / 100);
        } else {
            montos.push(base / 100);
            asignado += base;
        }
    }

    return montos;
}

export function usePlanCredito(getSaldo) {
    const numPagos = ref(2);
    const pagosPlan = ref([]);

    function rebuildPlan() {
        const saldo = Number(getSaldo()) || 0;
        const n = Math.max(2, Math.min(12, Number(numPagos.value) || 2));
        numPagos.value = n;

        const previo = pagosPlan.value;
        const hoy = ahoraDatetimeLocal();
        const maxFecha = maxFechaVencimientoBolivia();
        const montosSugeridos = distribuirMontos(saldo, n);

        pagosPlan.value = Array.from({ length: n }, (_, i) => {
            const existente = previo[i];
            let fecha = existente?.fecha;
            if (!fecha) {
                fecha = i === 0 ? hoy : fechaBoliviaDesplazada(i * 15);
            }
            if (i > 0 && fecha > maxFecha) {
                fecha = maxFecha;
            }

            const montoPrevio = existente?.monto;
            const conservarMonto =
                montoPrevio !== undefined && montoPrevio !== "" && Number(montoPrevio) > 0;

            return {
                monto: conservarMonto ? montoPrevio : montosSugeridos[i],
                fecha,
                esInicial: i === 0,
                etiqueta: i === 0 ? "Inicial (cobrar hoy)" : `Cuota ${i}`,
            };
        });

        ajustarSaldoRestante();
    }

    function ajustarSaldoRestante() {
        const saldo = Number(getSaldo()) || 0;
        if (pagosPlan.value.length < 2) return;

        const inicial = Number(pagosPlan.value[0]?.monto) || 0;
        const restante = Math.max(0, Math.round((saldo - inicial) * 100) / 100);
        const cuotasRestantes = pagosPlan.value.length - 1;

        if (cuotasRestantes === 1) {
            pagosPlan.value[1].monto = restante;
            return;
        }

        const porCuota = Math.floor((restante * 100) / cuotasRestantes) / 100;
        let asignado = 0;
        for (let i = 1; i < pagosPlan.value.length; i++) {
            if (i === pagosPlan.value.length - 1) {
                pagosPlan.value[i].monto = Math.round((restante - asignado) * 100) / 100;
            } else {
                pagosPlan.value[i].monto = porCuota;
                asignado += porCuota;
            }
        }
    }

    function initPlan() {
        numPagos.value = 2;
        pagosPlan.value = [];
        rebuildPlan();
    }

    watch(numPagos, () => rebuildPlan());

    watch(
        () => pagosPlan.value[0]?.monto,
        () => {
            if (pagosPlan.value.length > 1) {
                ajustarSaldoRestante();
            }
        }
    );

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

    const saldosPorFila = computed(() => {
        const saldo = Number(getSaldo()) || 0;
        let acumulado = 0;

        return pagosPlan.value.map((p, i) => {
            acumulado += Number(p.monto) || 0;
            return {
                fila: i,
                acumulado: Math.round(acumulado * 100) / 100,
                restante: Math.max(0, Math.round((saldo - acumulado) * 100) / 100),
            };
        });
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
        saldosPorFila,
        maxFechaVencimiento: maxFechaVencimientoBolivia,
        payloadCuotasPlan,
    };
}
