<script setup>
import { useTheme } from '@/composables/useTheme';

const { theme, mode, fontSize, contrast, setTheme, setMode, setFontSize, setContrast } = useTheme();

const temas = [
    { id: 'ninos', label: 'Niños' },
    { id: 'jovenes', label: 'Jóvenes' },
    { id: 'adultos', label: 'Adultos' },
];
const tamanos = [
    { id: 'small', label: 'A-' },
    { id: 'normal', label: 'A' },
    { id: 'large', label: 'A+' },
];
</script>

<template>
    <div class="dropdown">
        <button class="btn btn-sm btn-light" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Apariencia">
            <i class="bi bi-palette"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end p-3 shadow" style="min-width: 260px;">
            <div class="mb-2">
                <div class="small text-muted mb-1">Tema</div>
                <div class="btn-group w-100">
                    <button v-for="t in temas" :key="t.id" class="btn btn-sm" :class="theme === t.id ? 'btn-primary' : 'btn-outline-secondary'" @click="setTheme(t.id)">{{ t.label }}</button>
                </div>
            </div>
            <div class="mb-2">
                <div class="small text-muted mb-1">Modo</div>
                <div class="btn-group w-100">
                    <button class="btn btn-sm" :class="mode === 'dia' ? 'btn-primary' : 'btn-outline-secondary'" @click="setMode('dia')"><i class="bi bi-sun"></i> Día</button>
                    <button class="btn btn-sm" :class="mode === 'noche' ? 'btn-primary' : 'btn-outline-secondary'" @click="setMode('noche')"><i class="bi bi-moon"></i> Noche</button>
                    <button class="btn btn-sm" :class="mode === 'auto' ? 'btn-primary' : 'btn-outline-secondary'" @click="setMode('auto')">Auto</button>
                </div>
            </div>
            <div class="mb-2">
                <div class="small text-muted mb-1">Tamaño de letra</div>
                <div class="btn-group w-100">
                    <button v-for="t in tamanos" :key="t.id" class="btn btn-sm" :class="fontSize === t.id ? 'btn-primary' : 'btn-outline-secondary'" @click="setFontSize(t.id)">{{ t.label }}</button>
                </div>
            </div>
            <div>
                <div class="small text-muted mb-1">Contraste</div>
                <button class="btn btn-sm w-100" :class="contrast === 'high' ? 'btn-dark' : 'btn-outline-secondary'" @click="setContrast(contrast === 'high' ? 'normal' : 'high')">
                    {{ contrast === 'high' ? 'Alto contraste activado' : 'Activar alto contraste' }}
                </button>
            </div>
        </div>
    </div>
</template>
