import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    // No hace falta fijar `base`: laravel-vite-plugin toma el prefijo de
    // ASSET_URL (o APP_URL) del .env al compilar, así el build funciona igual
    // en http://rao_motos.test que en .../grupo02sa/proyecto2/public.
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',  // ← Importante incluir el CSS
                'resources/js/app.js'
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    /*
     * Direcciones RELATIVAS dentro del paquete compilado.
     *
     * Por defecto Vite escribe "/build/..." para cargar las páginas (cada
     * pantalla de Inertia es un trozo que se pide al vuelo). Esa barra inicial
     * apunta a la raíz del dominio, así que en el servidor —donde la app vive
     * en .../grupo02sa/proyecto2— buscaría los archivos en tecnoweb.org.bo/build
     * y no los encontraría: la página quedaría en blanco.
     *
     * Con `relative: true` cada trozo se busca a partir de la dirección del
     * archivo que lo pide, así el MISMO build funciona en http://rao_motos.test
     * y dentro del subdirectorio del servidor, sin recompilar.
     */
    experimental: {
        renderBuiltUrl: () => ({ relative: true }),
    },
});