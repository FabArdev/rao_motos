<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ orden: Object, productos: Array });
const page = usePage();
const rol = page.props.auth?.user?.rol;
const esAdmin = rol === 'admin';
const puede = (r) => esAdmin || rol === r;

const badge = (e) => ({ RECIBIDA: 'bg-secondary', DIAGNOSTICADA: 'bg-warning text-dark', EN_REPARACION: 'bg-info text-dark', TERMINADA: 'bg-primary', ENTREGADA: 'bg-success', CANCELADA: 'bg-danger', SOLICITADO: 'bg-warning text-dark', APROBADO: 'bg-primary', RECHAZADO: 'bg-danger', ENTREGADO: 'bg-success' }[e] ?? 'bg-secondary');
const fmt = (n) => `Bs. ${Number(n ?? 0).toFixed(2)}`;

// --- Diagnóstico (mecánico) ---
const diag = useForm({
    diagnostico: props.orden.diagnostico ?? '',
    costo_estimado_mano_obra: props.orden.costo_estimado_mano_obra ?? 0,
    costo_estimado_repuestos: props.orden.costo_estimado_repuestos ?? 0,
});
const guardarDiagnostico = () => diag.post(route('taller.diagnosticar', props.orden.id), { preserveScroll: true });

// --- Solicitar repuestos (mecánico) ---
const rep = useForm({ items: [{ producto_id: '', cantidad: 1 }] });
const addRep = () => rep.items.push({ producto_id: '', cantidad: 1 });
const delRep = (i) => rep.items.splice(i, 1);
const enviarRepuestos = () => rep.post(route('taller.solicitar-repuestos', props.orden.id), {
    preserveScroll: true,
    onSuccess: () => (rep.items = [{ producto_id: '', cantidad: 1 }]),
});

// --- Terminar (mecánico) ---
const fin = useForm({ costo_mano_obra: props.orden.costo_estimado_mano_obra ?? 0 });
const terminar = () => fin.post(route('taller.terminar', props.orden.id), { preserveScroll: true });

// --- Facturar (vendedor) ---
const fac = useForm({ tipo_venta: 'CONTADO', metodo_pago: 'EFECTIVO', numero_cuotas: 2, tasa_interes: null });
const facturar = () => fac.post(route('taller.facturar', props.orden.id), { preserveScroll: true });

const aprobarRep = (d) => router.post(route('taller.aprobar-repuesto', d.id), {}, { preserveScroll: true });
const rechazarRep = (d) => router.post(route('taller.rechazar-repuesto', d.id), {}, { preserveScroll: true });
const entregar = () => { if (confirm('¿Entregar la moto al cliente?')) router.post(route('taller.entregar', props.orden.id), {}, { preserveScroll: true }); };

const stockDe = (id) => props.productos.find((p) => p.id == id)?.inventario?.stock_actual ?? 0;
</script>

<template>
    <Head title="Orden de taller" />
    <AppLayout title="Orden de taller">
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">Orden #{{ orden.id }} <span class="badge ms-2" :class="badge(orden.estado)">{{ orden.estado }}</span></h5>
                    <div class="text-muted">{{ orden.cliente?.user?.name }} · {{ orden.moto?.marca }} {{ orden.moto?.modelo }} ({{ orden.moto?.placa }})</div>
                    <div class="mt-2"><strong>Problema:</strong> {{ orden.descripcion_problema }}</div>
                    <div v-if="orden.diagnostico" class="mt-1"><strong>Diagnóstico:</strong> {{ orden.diagnostico }}</div>
                    <div v-if="orden.venta" class="small mt-1">Venta: <Link :href="route('ventas.show', orden.venta.id)">{{ orden.venta.numero_venta }}</Link></div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <button v-if="orden.estado === 'TERMINADA' && orden.venta_id" class="btn btn-success" @click="entregar">Entregar moto</button>
                    <Link :href="route('taller.index')" class="btn btn-outline-secondary">Volver</Link>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Diagnóstico (mecánico) -->
            <div v-if="puede('mecanico') && ['RECIBIDA','DIAGNOSTICADA'].includes(orden.estado)" class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Diagnóstico y presupuesto</div>
                    <div class="card-body">
                        <div class="mb-2"><label class="form-label">Diagnóstico</label>
                            <textarea v-model="diag.diagnostico" rows="2" class="form-control" :class="{ 'is-invalid': diag.errors.diagnostico }"></textarea>
                            <div class="invalid-feedback">{{ diag.errors.diagnostico }}</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6"><label class="form-label small">Mano de obra (est.)</label><input v-model.number="diag.costo_estimado_mano_obra" type="number" step="0.01" class="form-control" /></div>
                            <div class="col-6"><label class="form-label small">Repuestos (est.)</label><input v-model.number="diag.costo_estimado_repuestos" type="number" step="0.01" class="form-control" /></div>
                        </div>
                        <button class="btn btn-primary btn-sm mt-3" @click="guardarDiagnostico" :disabled="diag.processing">Guardar diagnóstico</button>
                    </div>
                </div>
            </div>

            <!-- Presupuesto info -->
            <div v-if="orden.costo_estimado_mano_obra !== null" class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-semibold">Presupuesto</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between"><span>Mano de obra estimada</span><span>{{ fmt(orden.costo_estimado_mano_obra) }}</span></div>
                        <div class="d-flex justify-content-between"><span>Repuestos estimados</span><span>{{ fmt(orden.costo_estimado_repuestos) }}</span></div>
                        <hr class="my-2" />
                        <div class="d-flex justify-content-between fw-bold"><span>Total estimado</span><span>{{ fmt(Number(orden.costo_estimado_mano_obra) + Number(orden.costo_estimado_repuestos)) }}</span></div>
                        <div class="mt-2">
                            <span class="badge" :class="orden.presupuesto_aprobado ? 'bg-success' : 'bg-warning text-dark'">
                                {{ orden.presupuesto_aprobado ? 'Presupuesto aprobado por el cliente' : 'Esperando aprobación del cliente' }}
                            </span>
                        </div>
                        <div v-if="orden.costo_mano_obra !== null" class="mt-2 small text-muted">Mano de obra real: {{ fmt(orden.costo_mano_obra) }}</div>
                    </div>
                </div>
            </div>

            <!-- Repuestos -->
            <div v-if="orden.detalles?.length || (puede('mecanico') && orden.estado === 'EN_REPARACION')" class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">Repuestos</div>
                    <div class="table-responsive" v-if="orden.detalles?.length">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
                            <tbody>
                                <tr v-for="d in orden.detalles" :key="d.id">
                                    <td>{{ d.producto?.codigo }} — {{ d.producto?.nombre }}</td>
                                    <td class="text-end">{{ d.cantidad }}</td>
                                    <td><span class="badge" :class="badge(d.estado)">{{ d.estado }}</span></td>
                                    <td class="text-end">
                                        <template v-if="puede('almacenero') && d.estado === 'SOLICITADO'">
                                            <button class="btn btn-sm btn-success" @click="aprobarRep(d)">Aprobar</button>
                                            <button class="btn btn-sm btn-outline-danger ms-1" @click="rechazarRep(d)">Rechazar</button>
                                        </template>
                                        <span v-else-if="d.motivo" class="small text-muted">{{ d.motivo }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="puede('mecanico') && orden.estado === 'EN_REPARACION'" class="card-body border-top">
                        <div class="fw-semibold small mb-2">Solicitar repuestos al almacén</div>
                        <div v-for="(it, i) in rep.items" :key="i" class="row g-2 mb-2 align-items-center">
                            <div class="col-md-7">
                                <select v-model="it.producto_id" class="form-select form-select-sm">
                                    <option value="" disabled>Seleccione producto...</option>
                                    <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} — {{ p.nombre }} (stock {{ stockDe(p.id) }})</option>
                                </select>
                            </div>
                            <div class="col-md-3"><input v-model.number="it.cantidad" type="number" min="1" class="form-control form-control-sm" /></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-light text-danger" @click="delRep(i)" :disabled="rep.items.length===1"><i class="bi bi-trash"></i></button></div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary me-2" @click="addRep"><i class="bi bi-plus-lg"></i> Línea</button>
                        <button class="btn btn-sm btn-primary" @click="enviarRepuestos" :disabled="rep.processing">Solicitar</button>
                    </div>
                </div>
            </div>

            <!-- Terminar (mecánico) -->
            <div v-if="puede('mecanico') && orden.estado === 'EN_REPARACION'" class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">Terminar reparación</div>
                    <div class="card-body">
                        <label class="form-label small">Costo real de mano de obra</label>
                        <input v-model.number="fin.costo_mano_obra" type="number" step="0.01" class="form-control mb-2" />
                        <button class="btn btn-primary btn-sm" @click="terminar" :disabled="fin.processing">Marcar terminada</button>
                    </div>
                </div>
            </div>

            <!-- Facturar (vendedor) -->
            <div v-if="puede('vendedor') && orden.estado === 'TERMINADA' && !orden.venta_id" class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-semibold">Facturar orden</div>
                    <div class="card-body row g-2">
                        <div class="col-6"><label class="form-label small">Tipo</label>
                            <select v-model="fac.tipo_venta" class="form-select form-select-sm"><option value="CONTADO">Contado</option><option value="CREDITO">Crédito</option></select>
                        </div>
                        <div class="col-6"><label class="form-label small">Método</label>
                            <select v-model="fac.metodo_pago" class="form-select form-select-sm"><option value="EFECTIVO">Efectivo</option><option value="QR">QR</option></select>
                        </div>
                        <template v-if="fac.tipo_venta === 'CREDITO'">
                            <div class="col-6"><label class="form-label small">N° cuotas</label><input v-model.number="fac.numero_cuotas" type="number" min="2" class="form-control form-control-sm" /></div>
                            <div class="col-6"><label class="form-label small">Interés %</label><input v-model.number="fac.tasa_interes" type="number" step="0.01" class="form-control form-control-sm" /></div>
                        </template>
                        <div class="col-12"><button class="btn btn-success btn-sm mt-2" @click="facturar" :disabled="fac.processing">Generar venta</button></div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
