import axios from "axios";
import { ref } from "vue";

export function infoPagoQrConfirmada(info) {
    if (!info || typeof info !== "object") return false;
    if (Number(info.paymentStatus) === 1) return true;

    const desc = String(info.paymentStatusDescription || "").trim().toUpperCase();
    if (["PAGADO", "APROBADO", "COMPLETED", "PAID", "SUCCESS"].includes(desc)) {
        return true;
    }

    return Boolean(info.paymentDate && info.paymentTime);
}

export function esPagoQrConfirmado(data) {
    if (data?.pagado === true) return true;

    const candidatos = [data?.paymentInfo, data?.data, data].filter(Boolean);
    return candidatos.some((info) => infoPagoQrConfirmada(info));
}

export function normalizarInfoPagoQr(data) {
    const info = data?.paymentInfo ?? data?.data ?? {};
    return {
        paymentStatus: info.paymentStatus ?? data?.paymentStatus ?? null,
        paymentStatusDescription: info.paymentStatusDescription ?? null,
        amount: info.amount ?? null,
        currencyCode: info.currencyCode ?? "BOB",
        paymentMethodDetail: info.paymentMethodDetail ?? null,
        pagofacilTransactionId: info.pagofacilTransactionId != null
            ? String(info.pagofacilTransactionId)
            : null,
        companyTransactionId: info.companyTransactionId != null
            ? String(info.companyTransactionId)
            : null,
        requestDate: info.requestDate ?? null,
        requestTime: info.requestTime ?? null,
        paymentDate: info.paymentDate ?? null,
        paymentTime: info.paymentTime ?? null,
        payerName: info.payerName ?? null,
        payerDocument: info.payerDocument ?? null,
        payerAccount: info.payerAccount ?? null,
        payerBank: info.payerBank ?? null,
    };
}

export function textoEstadoPagoQr(status, descripcion) {
    if (infoPagoQrConfirmada({ paymentStatus: status, paymentStatusDescription: descripcion })) {
        return descripcion || "Pagado";
    }
    const map = {
        1: "Pagado",
        2: "Pendiente",
        3: "Expirado",
        4: "Cancelado",
    };
    return map[status] ?? descripcion ?? "Desconocido";
}

export function formatearFechaHoraPagoQr(fecha, hora) {
    if (!fecha) return "—";
    const horaTxt = hora ? ` ${hora}` : "";
    return `${fecha}${horaTxt}`;
}

export function aplicarInfoPagoQrAForm(form, info, pagosPlan = null) {
    if (!form || !info) return;

    form.id_transaccion_externa =
        info.pagofacilTransactionId ||
        info.companyTransactionId ||
        form.id_transaccion_externa ||
        "";

    if (info.amount != null && info.amount !== "") {
        form.monto = Number(info.amount);
    }

    if (info.paymentDate) {
        const hora = (info.paymentTime || "00:00:00").slice(0, 5);
        form.fecha_pago = `${info.paymentDate}T${hora}`;
    } else if (!form.fecha_pago) {
        form.fecha_pago = new Date().toISOString().slice(0, 16);
    }

    if (Array.isArray(pagosPlan) && pagosPlan.length > 0) {
        if (info.amount != null && info.amount !== "") {
            pagosPlan[0].monto = Number(info.amount);
        }
        if (form.fecha_pago) {
            pagosPlan[0].fecha = form.fecha_pago;
        }
    }
}

function payloadVerificarPago(numeroTransaccion, numeroPago) {
    const payload = {};
    const tx = numeroTransaccion != null && String(numeroTransaccion).trim() !== ""
        ? String(numeroTransaccion).trim()
        : "";
    const ref = numeroPago != null && String(numeroPago).trim() !== ""
        ? String(numeroPago).trim()
        : "";
    if (tx) payload.numeroTransaccion = tx;
    if (ref) payload.numeroPago = ref;
    return payload;
}

export function usePagoQr({
    onError,
    esperaInicialMs = 30000,
} = {}) {
    const pagoQrImage = ref("");
    const pagoQrTransaccion = ref("");
    const pagoQrNumeroPago = ref("");
    const pagoQrVerificando = ref(false);
    const pagoQrEsperando = ref(false);
    const pagoQrVerificacionLista = ref(false);
    const pagoQrInfo = ref(null);
    const showConfirmacionPago = ref(false);
    let pagoQrDelayTimeout = null;

    function limpiarQrPago() {
        if (pagoQrDelayTimeout) {
            clearTimeout(pagoQrDelayTimeout);
            pagoQrDelayTimeout = null;
        }
        pagoQrImage.value = "";
        pagoQrTransaccion.value = "";
        pagoQrNumeroPago.value = "";
        pagoQrVerificando.value = false;
        pagoQrEsperando.value = false;
        pagoQrVerificacionLista.value = false;
        pagoQrInfo.value = null;
        showConfirmacionPago.value = false;
    }

    async function generarQrPago(payload) {
        try {
            const { data } = await axios.post("/api/generar-qr", payload);
            if (!data.success || !data.qrImage) {
                onError?.(data.message || "No se pudo generar el código QR.");
                return false;
            }
            pagoQrImage.value = `data:image/png;base64,${data.qrImage}`;
            pagoQrTransaccion.value =
                data.numeroTransaccion != null ? String(data.numeroTransaccion) : "";
            pagoQrNumeroPago.value =
                data.numeroPago != null ? String(data.numeroPago) : "";
            pagoQrInfo.value = null;
            pagoQrVerificacionLista.value = false;
            showConfirmacionPago.value = false;
            return true;
        } catch (e) {
            onError?.(e.response?.data?.message || "Error al generar el código QR.");
            return false;
        }
    }

    async function verificarQrPagoDetalle() {
        const body = payloadVerificarPago(pagoQrTransaccion.value, pagoQrNumeroPago.value);
        if (!body.numeroTransaccion && !body.numeroPago) {
            return { pagado: false, info: null };
        }
        try {
            const { data } = await axios.post("/api/verificar-pago", body);
            const info = normalizarInfoPagoQr(data);
            if (!esPagoQrConfirmado(data)) {
                return { pagado: false, info };
            }
            pagoQrInfo.value = info;
            if (info.pagofacilTransactionId) {
                pagoQrTransaccion.value = info.pagofacilTransactionId;
            }
            showConfirmacionPago.value = true;
            return { pagado: true, info };
        } catch {
            return { pagado: false, info: null };
        }
    }

    async function verificarQrPago() {
        const resultado = await verificarQrPagoDetalle();
        return resultado.pagado;
    }

    async function ejecutarVerificacionUnica() {
        pagoQrVerificando.value = true;
        pagoQrVerificacionLista.value = false;
        try {
            const resultado = await verificarQrPagoDetalle();
            pagoQrVerificacionLista.value = true;
            if (!resultado.pagado) {
                onError?.(
                    "El pago QR aún no está confirmado. Escanee el código y pulse «Verificar pago»."
                );
            }
            return resultado;
        } finally {
            pagoQrVerificando.value = false;
        }
    }

    function iniciarVerificacionQrPago() {
        if (pagoQrDelayTimeout) {
            clearTimeout(pagoQrDelayTimeout);
            pagoQrDelayTimeout = null;
        }

        pagoQrVerificando.value = false;
        pagoQrEsperando.value = true;
        pagoQrVerificacionLista.value = false;

        pagoQrDelayTimeout = setTimeout(async () => {
            pagoQrDelayTimeout = null;
            pagoQrEsperando.value = false;
            await ejecutarVerificacionUnica();
        }, esperaInicialMs);
    }

    async function reintentarVerificacionQrPago() {
        if (pagoQrDelayTimeout) {
            clearTimeout(pagoQrDelayTimeout);
            pagoQrDelayTimeout = null;
        }
        pagoQrEsperando.value = false;
        return ejecutarVerificacionUnica();
    }

    function cerrarConfirmacionPago() {
        showConfirmacionPago.value = false;
    }

    function textoEstadoVerificacionQr() {
        if (pagoQrEsperando.value) {
            return "Escanee el QR con su app bancaria. Consultaremos el pago en 30 segundos...";
        }
        if (pagoQrVerificando.value) {
            return "Consultando estado del pago...";
        }
        if (pagoQrVerificacionLista.value && !showConfirmacionPago.value) {
            return "Pago no confirmado aún. Pulse «Verificar pago» cuando el cliente haya pagado.";
        }
        return "Escanee el QR para completar el cobro.";
    }

    return {
        pagoQrImage,
        pagoQrTransaccion,
        pagoQrNumeroPago,
        pagoQrVerificando,
        pagoQrEsperando,
        pagoQrVerificacionLista,
        pagoQrInfo,
        showConfirmacionPago,
        limpiarQrPago,
        generarQrPago,
        verificarQrPago,
        verificarQrPagoDetalle,
        iniciarVerificacionQrPago,
        reintentarVerificacionQrPago,
        cerrarConfirmacionPago,
        textoEstadoVerificacionQr,
    };
}
