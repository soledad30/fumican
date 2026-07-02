import axios from "axios";
import { ref } from "vue";

export function esPagoQrConfirmado(data) {
    return (
        data?.pagado === true ||
        data?.paymentStatus === 1 ||
        data?.data?.paymentStatus === 1 ||
        data?.paymentInfo?.paymentStatus === 1
    );
}

export function normalizarInfoPagoQr(data) {
    const info = data?.paymentInfo ?? data?.data ?? {};
    return {
        paymentStatus: info.paymentStatus ?? data?.paymentStatus ?? null,
        paymentStatusDescription: info.paymentStatusDescription ?? null,
        amount: info.amount ?? null,
        currencyCode: info.currencyCode ?? "BOB",
        paymentMethodDetail: info.paymentMethodDetail ?? null,
        pagofacilTransactionId: info.pagofacilTransactionId ?? null,
        companyTransactionId: info.companyTransactionId ?? null,
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

export function textoEstadoPagoQr(status) {
    const map = {
        1: "Pagado",
        2: "Pendiente",
        3: "Expirado",
        4: "Cancelado",
    };
    return map[status] ?? "Desconocido";
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

export function usePagoQr({ onError, intervalMs = 5000, maxIntentos = 12 } = {}) {
    const pagoQrImage = ref("");
    const pagoQrTransaccion = ref("");
    const pagoQrNumeroPago = ref("");
    const pagoQrVerificando = ref(false);
    const pagoQrInfo = ref(null);
    const showConfirmacionPago = ref(false);
    let pagoQrInterval = null;

    function limpiarQrPago() {
        if (pagoQrInterval) {
            clearInterval(pagoQrInterval);
            pagoQrInterval = null;
        }
        pagoQrImage.value = "";
        pagoQrTransaccion.value = "";
        pagoQrNumeroPago.value = "";
        pagoQrVerificando.value = false;
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
            pagoQrTransaccion.value = data.numeroTransaccion || "";
            pagoQrNumeroPago.value = data.numeroPago || "";
            pagoQrInfo.value = null;
            showConfirmacionPago.value = false;
            return true;
        } catch (e) {
            onError?.(e.response?.data?.message || "Error al generar el código QR.");
            return false;
        }
    }

    async function verificarQrPagoDetalle() {
        if (!pagoQrTransaccion.value && !pagoQrNumeroPago.value) {
            return { pagado: false, info: null };
        }
        try {
            const { data } = await axios.post("/api/verificar-pago", {
                numeroTransaccion: pagoQrTransaccion.value || undefined,
                numeroPago: pagoQrNumeroPago.value || undefined,
            });
            if (!esPagoQrConfirmado(data)) {
                return { pagado: false, info: null };
            }
            const info = normalizarInfoPagoQr(data);
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

    function iniciarVerificacionQrPago() {
        if (pagoQrInterval) clearInterval(pagoQrInterval);
        pagoQrVerificando.value = true;
        let intentos = 0;

        pagoQrInterval = setInterval(async () => {
            intentos++;
            const resultado = await verificarQrPagoDetalle();
            if (resultado.pagado) {
                clearInterval(pagoQrInterval);
                pagoQrInterval = null;
                pagoQrVerificando.value = false;
                return;
            }
            if (intentos >= maxIntentos) {
                clearInterval(pagoQrInterval);
                pagoQrInterval = null;
                pagoQrVerificando.value = false;
                onError?.("El pago QR no fue confirmado. Escanee el código e intente de nuevo.");
            }
        }, intervalMs);
    }

    function cerrarConfirmacionPago() {
        showConfirmacionPago.value = false;
    }

    return {
        pagoQrImage,
        pagoQrTransaccion,
        pagoQrNumeroPago,
        pagoQrVerificando,
        pagoQrInfo,
        showConfirmacionPago,
        limpiarQrPago,
        generarQrPago,
        verificarQrPago,
        verificarQrPagoDetalle,
        iniciarVerificacionQrPago,
        cerrarConfirmacionPago,
    };
}
