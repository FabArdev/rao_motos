<script setup>
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';

/**
 * Pantalla de pago por QR (PagoFácil), genérica para cuota o venta.
 * El controlador arma las URLs con route() y las pasa aquí.
 */
const props = defineProps({
    titulo: String,        // "Cuota #3" / "Venta V-0001"
    monto: Number,
    qr: Object,            // { qr_image, payment_number, transaction_id, simulado }
    estadoUrl: String,     // GET → { pagado: bool }
    volverUrl: String,     // a dónde volver al terminar
    descarga: { type: String, default: 'qr-pago.png' },
});

const esSimulado = props.qr?.simulado === true;
const pagado = ref(false);
let pollTimer = null;

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;

/* ── Vigencia del QR ────────────────────────────────────────────────
 * El QR de PagoFácil dura muy poco (≈2 min). Cuando vence se pide uno
 * nuevo al servidor (que descarta el anterior). Se limita el número de
 * renovaciones automáticas para no dejar la pantalla generando
 * transacciones sin nadie delante.
 */
const MAX_RENOVACIONES = 5;
// El contador vive en sessionStorage porque cada renovación remonta el componente.
const claveRenovaciones = `qr-renovaciones:${props.estadoUrl}`;
const renovaciones = ref(Number(sessionStorage.getItem(claveRenovaciones) || 0));
const segundosRestantes = ref(null);
const vencido = ref(false);
const renovando = ref(false);
let expiraTimer = null;

const cuentaRegresiva = computed(() => {
    if (segundosRestantes.value === null) return null;
    const s = Math.max(0, segundosRestantes.value);
    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
});

const renovarQr = () => {
    if (renovando.value || pagado.value) return;
    renovando.value = true;
    // Recargar la vista hace que el controlador genere y guarde un QR nuevo.
    router.reload({
        onFinish: () => { renovando.value = false; },
    });
};

const tickExpiracion = () => {
    if (!props.qr?.expiration) return;
    const restante = Math.round((new Date(props.qr.expiration).getTime() - Date.now()) / 1000);
    segundosRestantes.value = restante;

    if (restante > 0 || vencido.value || pagado.value) return;

    vencido.value = true;
    clearInterval(expiraTimer);

    if (renovaciones.value < MAX_RENOVACIONES) {
        renovaciones.value += 1;
        sessionStorage.setItem(claveRenovaciones, String(renovaciones.value));
        renovarQr();
    }
};

/** Renovación manual: reinicia el límite porque hay alguien mirando. */
const renovarManual = () => {
    renovaciones.value = 0;
    sessionStorage.setItem(claveRenovaciones, '0');
    renovarQr();
};

const guardarQr = () => {
    const image = new Image();
    image.onload = () => {
        const canvas = document.createElement('canvas');
        canvas.width = image.width;
        canvas.height = image.height;
        canvas.getContext('2d').drawImage(image, 0, 0);
        canvas.toBlob((blob) => {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = props.descarga;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        });
    };
    image.src = props.qr.qr_image;
};

const verificarEstado = async () => {
    try {
        const res = await fetch(props.estadoUrl);
        if (!res.ok) return;
        const data = await res.json();
        if (data.pagado) {
            pagado.value = true;
            clearInterval(pollTimer);
            setTimeout(() => { window.location.href = props.volverUrl; }, 2000);
        }
    } catch {
        // ignore
    }
};

onMounted(async () => {
    const res = await fetch(props.estadoUrl);
    const data = await res.json();
    if (data.pagado) {
        window.location.href = props.volverUrl;
        return;
    }
    if (!esSimulado) {
        pollTimer = setInterval(verificarEstado, 5000);
    }
    if (props.qr?.expiration) {
        tickExpiracion();
        expiraTimer = setInterval(tickExpiracion, 1000);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
    if (expiraTimer) clearInterval(expiraTimer);
});
</script>

<template>
    <Head title="Pago con QR" />
    <AppLayout title="Pago con QR — PagoFácil">
        <div class="card shadow-sm border-0 mx-auto text-center" style="max-width: 420px;">
            <div class="card-body">
                <h5 class="fw-bold">{{ titulo }}</h5>
                <div class="fs-4 text-primary mb-3">{{ fmt(monto) }}</div>

                <div v-if="esSimulado" class="alert alert-danger mb-3 py-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>QR no funcional:</strong> la API de PagoFácil no respondió. Este código QR <strong>no puede ser escaneado</strong> para pagar. Usa el botón "Ya realicé el pago" solo si el cliente pagó en caja.
                </div>

                <div v-if="pagado" class="alert alert-success mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    <strong>¡Pago confirmado!</strong> Redirigiendo...
                </div>

                <div v-if="vencido && !pagado" class="alert alert-warning mb-3 py-2 small">
                    <i class="bi bi-clock-history me-1"></i>
                    <span v-if="renovando">El QR venció. Generando uno nuevo...</span>
                    <span v-else><strong>El QR venció</strong> y ya no puede escanearse. Genera uno nuevo para continuar.</span>
                </div>

                <div v-if="qr.qr_image && !pagado" class="position-relative d-inline-block mb-3">
                    <img :src="qr.qr_image" alt="QR PagoFácil" class="img-fluid border rounded" :class="{ 'opacity-25': vencido }" style="max-width: 260px;" />
                </div>
                <div v-else-if="!qr.qr_image && !pagado" class="alert alert-warning">No se recibió la imagen del QR.</div>

                <p v-if="!pagado && !vencido" class="text-muted small mb-1">Escanea el QR con tu app bancaria.</p>

                <p v-if="!pagado && !vencido && cuentaRegresiva" class="small mb-2">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Válido por <strong>{{ cuentaRegresiva }}</strong>
                </p>

                <div v-if="!pagado && !vencido" class="d-flex align-items-center justify-content-center gap-2 text-muted small mb-2">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    <span>Esperando confirmación de pago...</span>
                </div>

                <div v-if="!pagado" class="d-grid gap-2">
                    <button v-if="vencido" class="btn btn-primary" :disabled="renovando" @click="renovarManual">
                        <i class="bi bi-arrow-clockwise me-1"></i>{{ renovando ? 'Generando...' : 'Generar nuevo QR' }}
                    </button>
                    <button v-else class="btn btn-outline-primary" @click="guardarQr"><i class="bi bi-download me-1"></i>Guardar QR</button>
                    <a :href="volverUrl" class="btn btn-outline-secondary">Volver</a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
