import axios from "axios";
import { ref } from "vue";

export function esPagoQrConfirmado(data) {
    return (
        data?.pagado === true ||
        data?.paymentStatus === 1 ||
        data?.data?.paymentStatus === 1
    );
}

export function usePagoQr({ onPagado, onError, intervalMs = 5000, maxIntentos = 12 } = {}) {
    const pagoQrImage = ref("");
    const pagoQrTransaccion = ref("");
    const pagoQrNumeroPago = ref("");
    const pagoQrVerificando = ref(false);
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
            return true;
        } catch (e) {
            onError?.(e.response?.data?.message || "Error al generar el código QR.");
            return false;
        }
    }

    async function verificarQrPago() {
        if (!pagoQrTransaccion.value && !pagoQrNumeroPago.value) return false;
        try {
            const { data } = await axios.post("/api/verificar-pago", {
                numeroTransaccion: pagoQrTransaccion.value || undefined,
                numeroPago: pagoQrNumeroPago.value || undefined,
            });
            return esPagoQrConfirmado(data);
        } catch {
            return false;
        }
    }

    function iniciarVerificacionQrPago() {
        if (pagoQrInterval) clearInterval(pagoQrInterval);
        pagoQrVerificando.value = true;
        let intentos = 0;

        pagoQrInterval = setInterval(async () => {
            intentos++;
            if (await verificarQrPago()) {
                clearInterval(pagoQrInterval);
                pagoQrInterval = null;
                pagoQrVerificando.value = false;
                await onPagado?.();
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

    return {
        pagoQrImage,
        pagoQrTransaccion,
        pagoQrNumeroPago,
        pagoQrVerificando,
        limpiarQrPago,
        generarQrPago,
        verificarQrPago,
        iniciarVerificacionQrPago,
    };
}
