<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ registros: Object, filtros: Object });
const accion = ref(props.filtros?.accion ?? '');
const q = ref(props.filtros?.q ?? '');

const filtrar = () => router.get(route('bitacora.index'), { accion: accion.value, q: q.value }, { preserveState: true, replace: true });

const badge = (a) => ({ LOGIN_OK: 'bg-success', LOGIN_FAIL: 'bg-danger', ACCESO_RECURSO: 'bg-secondary' }[a] ?? 'bg-secondary');
</script>

<template>
    <Head title="Bitácora" />
    <AppLayout title="Bitácora de accesos">
        <div class="d-flex gap-2 mb-3">
            <select v-model="accion" class="form-select" style="max-width: 200px;" @change="filtrar">
                <option value="">Todas las acciones</option>
                <option value="LOGIN_OK">Login OK</option>
                <option value="LOGIN_FAIL">Login fallido</option>
                <option value="ACCESO_RECURSO">Acceso a recurso</option>
            </select>
            <form class="d-flex gap-2" @submit.prevent="filtrar" style="max-width: 320px;">
                <input v-model="q" class="form-control" placeholder="Email o recurso..." />
                <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Fecha</th><th>Usuario</th><th>Email</th><th>Acción</th><th>Recurso</th><th>IP</th></tr></thead>
                    <tbody>
                        <tr v-for="r in registros.data" :key="r.id">
                            <td class="small">{{ new Date(r.fecha).toLocaleString() }}</td>
                            <td>{{ r.usuario?.nombre }} {{ r.usuario?.apellidos }}</td>
                            <td class="small">{{ r.email }}</td>
                            <td><span class="badge" :class="badge(r.accion)">{{ r.accion }}</span></td>
                            <td class="small font-monospace">{{ r.recurso }}</td>
                            <td class="small">{{ r.ip }}</td>
                        </tr>
                        <tr v-if="!registros.data.length"><td colspan="6" class="text-center text-muted py-4">Sin registros.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <nav v-if="registros.links.length > 3" class="mt-3">
            <ul class="pagination pagination-sm">
                <li v-for="(link, i) in registros.links" :key="i" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                    <Link v-if="link.url" class="page-link" :href="link.url" v-html="link.label" preserve-scroll />
                    <span v-else class="page-link" v-html="link.label" />
                </li>
            </ul>
        </nav>
    </AppLayout>
</template>
