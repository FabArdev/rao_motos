<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
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
    confirmarUrl: String,  // POST "ya realicé el pago"
    volverUrl: String,     // a dónde volver al terminar
    descarga: { type: String, default: 'qr-pago.png' },
});

const user = usePage().props.auth.user;
const esStaff = computed(() => user?.rol === 'admin' || user?.rol === 'vendedor');
const esSimulado = props.qr?.simulado === true;
const pagado = ref(false);
let pollTimer = null;

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;

const yaPague = async () => {
    await fetch(props.confirmarUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ payment_number: props.qr.payment_number, transaction_id: props.qr.transaction_id }),
    });
    router.visit(props.volverUrl);
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
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
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

                <img v-if="qr.qr_image && !pagado" :src="qr.qr_image" alt="QR PagoFácil" class="img-fluid border rounded mb-3" style="max-width: 260px;" />
                <div v-else-if="!qr.qr_image && !pagado" class="alert alert-warning">No se recibió la imagen del QR.</div>

                <p v-if="!pagado" class="text-muted small mb-1">Escanea el QR con tu app bancaria.</p>

                <div v-if="!pagado" class="d-flex align-items-center justify-content-center gap-2 text-muted small mb-2">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    <span>Esperando confirmación de pago...</span>
                </div>

                <div v-if="!pagado" class="d-grid gap-2">
                    <button class="btn btn-outline-primary" @click="guardarQr"><i class="bi bi-download me-1"></i>Guardar QR</button>
                    <button v-if="esStaff || esSimulado" class="btn btn-success" @click="yaPague"><i class="bi bi-check-circle me-1"></i>Ya realicé el pago</button>
                    <a :href="volverUrl" class="btn btn-outline-secondary">Volver</a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
