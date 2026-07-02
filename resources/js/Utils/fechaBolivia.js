export const ZONA_HORARIA_BOLIVIA = "America/La_Paz";

function partesEnBolivia(fecha = new Date()) {
    const date = fecha instanceof Date ? fecha : parseFecha(fecha);
    if (!date) {
        return null;
    }

    const formatter = new Intl.DateTimeFormat("en-CA", {
        timeZone: ZONA_HORARIA_BOLIVIA,
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false,
    });

    const parts = formatter.formatToParts(date);
    const get = (type) => parts.find((part) => part.type === type)?.value ?? "00";

    return {
        year: get("year"),
        month: get("month"),
        day: get("day"),
        hour: get("hour"),
        minute: get("minute"),
        second: get("second"),
    };
}

export function normalizarFechaYmd(fecha) {
    if (!fecha) {
        return null;
    }

    const str = String(fecha).trim();
    const dmY = str.match(/^(\d{2})-(\d{2})-(\d{4})/);
    if (dmY) {
        return `${dmY[3]}-${dmY[2]}-${dmY[1]}`;
    }

    const soloFecha = str.slice(0, 10);
    const partes = soloFecha.split("-");
    if (partes.length === 3 && partes[0].length === 4) {
        return soloFecha;
    }

    return soloFecha;
}

export function parseFecha(valor) {
    if (!valor) {
        return null;
    }

    if (valor instanceof Date) {
        return Number.isNaN(valor.getTime()) ? null : valor;
    }

    const str = String(valor).trim();
    const dmY = str.match(/^(\d{2})-(\d{2})-(\d{4})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
    if (dmY) {
        return new Date(
            `${dmY[3]}-${dmY[2]}-${dmY[1]}T${dmY[4] || "00"}:${dmY[5] || "00"}:${dmY[6] || "00"}-04:00`
        );
    }

    const iso = new Date(str);
    return Number.isNaN(iso.getTime()) ? null : iso;
}

export function ahoraDatetimeLocal() {
    const partes = partesEnBolivia(new Date());
    return `${partes.year}-${partes.month}-${partes.day}T${partes.hour}:${partes.minute}`;
}

export function ahoraFechaYmd() {
    const partes = partesEnBolivia(new Date());
    return `${partes.year}-${partes.month}-${partes.day}`;
}

export function ahoraHoraHms() {
    const partes = partesEnBolivia(new Date());
    return `${partes.hour}:${partes.minute}:${partes.second}`;
}

export function fechaBoliviaDesplazada(dias = 0) {
    const fecha = new Date();
    fecha.setDate(fecha.getDate() + dias);
    const partes = partesEnBolivia(fecha);
    return `${partes.year}-${partes.month}-${partes.day}`;
}

export function maxFechaVencimientoBolivia() {
    const fecha = new Date();
    fecha.setMonth(fecha.getMonth() + 1);
    const partes = partesEnBolivia(fecha);
    return `${partes.year}-${partes.month}-${partes.day}`;
}

export function esFechaHoyBolivia(fechaYmd) {
    if (!fechaYmd) {
        return true;
    }

    return normalizarFechaYmd(fechaYmd) === ahoraFechaYmd();
}

export function timestampDesdeFechaHoraBolivia(fechaYmd, hora = "00:00:00") {
    const ymd = normalizarFechaYmd(fechaYmd);
    if (!ymd) {
        return NaN;
    }

    const horaNormalizada = String(hora).length === 5 ? `${hora}:00` : String(hora).slice(0, 8);
    return Date.parse(`${ymd}T${horaNormalizada}-04:00`);
}

export function formatearFechaHora(valor, opciones = {}) {
    if (!valor) {
        return "—";
    }

    const fecha = parseFecha(valor);
    if (!fecha) {
        const dmY = String(valor).match(/^(\d{2})-(\d{2})-(\d{4})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/);
        if (dmY) {
            const hora = dmY[4] ? `, ${dmY[4]}:${dmY[5]}` : "";
            return `${dmY[1]}/${dmY[2]}/${dmY[3]}${hora}`;
        }

        return "—";
    }

    return fecha.toLocaleString("es-BO", {
        timeZone: ZONA_HORARIA_BOLIVIA,
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        ...opciones,
    });
}

export function formatearFecha(valor) {
    if (!valor) {
        return "—";
    }

    const fecha = parseFecha(valor);
    if (!fecha) {
        const dmY = String(valor).match(/^(\d{2})-(\d{2})-(\d{4})/);
        if (dmY) {
            return `${dmY[1]}/${dmY[2]}/${dmY[3]}`;
        }

        return "—";
    }

    return fecha.toLocaleDateString("es-BO", {
        timeZone: ZONA_HORARIA_BOLIVIA,
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
}

export function paraDatetimeLocal(valor) {
    if (!valor) {
        return "";
    }

    const str = String(valor).trim();
    const dmY = str.match(/^(\d{2})-(\d{2})-(\d{4})(?:[ T](\d{2}):(\d{2}))?/);
    if (dmY) {
        return `${dmY[3]}-${dmY[2]}-${dmY[1]}T${dmY[4] || "00"}:${dmY[5] || "00"}`;
    }

    const fecha = parseFecha(valor);
    if (!fecha) {
        return "";
    }

    const partes = partesEnBolivia(fecha);
    return `${partes.year}-${partes.month}-${partes.day}T${partes.hour}:${partes.minute}`;
}

export function fechaParaInput(valor) {
    if (!valor) {
        return "";
    }

    const ymd = normalizarFechaYmd(valor);
    return ymd || "";
}
