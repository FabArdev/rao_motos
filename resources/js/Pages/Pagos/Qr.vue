<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({ cuota: Object, monto: Number, qr: Object, redirectRoute: { type: String, default: 'mis-creditos.show' }, redirectParams: { type: Object, default: () => ({}) } });

const user = usePage().props.auth.user;
const esAdminOVendedor = computed(() => user?.rol === 'admin' || user?.rol === 'vendedor');
const esSimulado = props.qr?.simulado === true;
const pagado = ref(false);
let pollTimer = null;

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;

const redirigir = () => {
    router.visit(route(props.redirectRoute, props.redirectParams));
};

const yaPague = async () => {
    await fetch(route('pagofacil.confirmar-cuota'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ payment_number: props.qr.payment_number, transaction_id: props.qr.transaction_id }),
    });
    redirigir();
};

const verificarEstado = async () => {
    try {
        const res = await fetch(route('pagofacil.estado-cuota', props.cuota.id));
        if (!res.ok) return;
        const data = await res.json();
        if (data.pagado) {
            pagado.value = true;
            clearInterval(pollTimer);
            setTimeout(() => {
                window.location.href = route(props.redirectRoute, props.redirectParams);
            }, 2000);
        }
    } catch {
        // ignore
    }
};

onMounted(async () => {
    const res = await fetch(route('pagofacil.estado-cuota', props.cuota.id));
    const data = await res.json();
    if (data.pagado) {
        window.location.href = route(props.redirectRoute, props.redirectParams);
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
                <h5 class="fw-bold">Cuota #{{ cuota.numero_cuota }}</h5>
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
                <p v-if="!pagado" class="text-muted small"><i class="bi bi-arrow-repeat me-1"></i>Esperando confirmación de pago (consultando cada 5s)...</p>

                <div v-if="!pagado" class="d-grid gap-2">
                    <button v-if="esAdminOVendedor || esSimulado" class="btn btn-success" @click="yaPague"><i class="bi bi-check-circle me-1"></i>Ya realicé el pago</button>
                    <a :href="route(redirectRoute, redirectParams)" class="btn btn-outline-secondary">Volver</a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
