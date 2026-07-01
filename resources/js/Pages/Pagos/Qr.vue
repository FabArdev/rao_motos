<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ cuota: Object, monto: Number, qr: Object });

const fmt = (n) => `Bs. ${Number(n).toFixed(2)}`;

// Botón de demo que simula el callback de PagoFácil (webhook JSON) confirmando el pago.
const yaPague = async () => {
    await fetch(route('pagofacil.confirmar-cuota'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ payment_number: props.qr.payment_number, transaction_id: props.qr.transaction_id }),
    });
    router.visit(route('mis-creditos.show', props.cuota.credito_id));
};
</script>

<template>
    <Head title="Pago con QR" />
    <AppLayout title="Pago con QR — PagoFácil">
        <div class="card shadow-sm border-0 mx-auto text-center" style="max-width: 420px;">
            <div class="card-body">
                <h5 class="fw-bold">Cuota #{{ cuota.numero_cuota }}</h5>
                <div class="fs-4 text-primary mb-3">{{ fmt(monto) }}</div>

                <img v-if="qr.qr_image" :src="qr.qr_image" alt="QR PagoFácil" class="img-fluid border rounded mb-3" style="max-width: 260px;" />
                <div v-else class="alert alert-warning">No se recibió la imagen del QR.</div>

                <p class="text-muted small">Escanea el QR con tu app bancaria. El pago se confirma automáticamente por PagoFácil.</p>

                <div class="d-grid gap-2">
                    <button class="btn btn-success" @click="yaPague"><i class="bi bi-check-circle me-1"></i>Ya realicé el pago</button>
                    <Link :href="route('mis-creditos.show', cuota.credito_id)" class="btn btn-outline-secondary">Volver</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
