<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\BuscarController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DespachoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\MiCuentaController;
use App\Http\Controllers\MisCreditosController;
use App\Http\Controllers\MisPedidosController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes — RAO MOTOS
|--------------------------------------------------------------------------
| Las rutas de autenticación las registra Jetstream/Fortify.
| Las rutas de negocio se agregan por CU (usuarios, productos, ventas, ...).
*/

/*
 * Entrega de archivos subidos (fotos de producto, galería, foto de perfil)
 * SIN depender del enlace public/storage. En el servidor de la facultad
 * `artisan storage:link` normalmente no puede crear el enlace simbólico y todas
 * las imágenes salen rotas; App\Support\Media usa esta ruta como respaldo.
 * Es pública a propósito: son las mismas imágenes que serviría public/storage.
 */
Route::get('media/{ruta}', function (string $ruta) {
    $disco = Storage::disk('public');

    // El archivo debe quedar dentro del disco: corta ../ y rutas absolutas.
    $ruta = ltrim(str_replace('\\', '/', $ruta), '/');
    if (str_contains($ruta, '..') || ! $disco->exists($ruta)) {
        abort(404);
    }

    return $disco->response($ruta, null, [
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('ruta', '.*')->name('media.mostrar');

Route::get('/', function () {
    // Catálogo público de repuestos para la vitrina de inicio.
    $productos = \App\Models\Producto::with('inventario:id,producto_id,stock_actual')
        ->where('activo', true)
        ->orderByDesc('id')
        ->limit(8)
        ->get(['id', 'codigo', 'nombre', 'marca', 'modelo', 'precio_venta_base', 'precio_mayorista', 'cantidad_minima_mayorista', 'foto_url']);

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'productos' => $productos,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'bitacora',
    'track.visits',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // CU1 — Gestión de usuarios (solo admin)
    Route::middleware('rol:admin')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->parameters(['usuarios' => 'usuario']);
    });

    // CU2 — Gestión de productos / CU3 Proveedores·Compras / CU5 Inventario (almacenero; admin superusuario)
    Route::middleware('rol:admin,almacenero')->group(function () {
        Route::resource('productos', ProductoController::class)->parameters(['productos' => 'producto']);
        Route::resource('proveedores', ProveedorController::class)->parameters(['proveedores' => 'proveedor']);

        Route::post('compras/{compra}/recibir', [CompraController::class, 'recibir'])->name('compras.recibir');
        Route::post('compras/{compra}/anular', [CompraController::class, 'anular'])->name('compras.anular');
        Route::resource('compras', CompraController::class)->parameters(['compras' => 'compra'])->except(['edit', 'update']);

        Route::get('inventario', [InventarioController::class, 'index'])->name('inventario.index');
        Route::get('inventario/{inventario}', [InventarioController::class, 'show'])->name('inventario.show');
        Route::post('inventario/{inventario}/ajuste', [InventarioController::class, 'ajuste'])->name('inventario.ajuste');
    });

    // CU6 — Ventas (vendedor; admin superusuario)
    Route::middleware('rol:admin,vendedor')->group(function () {
        Route::post('ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
        Route::post('ventas/{venta}/marcar-pagada', [VentaController::class, 'marcarPagada'])->name('ventas.marcar-pagada');
        Route::resource('ventas', VentaController::class)->parameters(['ventas' => 'venta'])->only(['index', 'create', 'store', 'show']);

        // Cobro por QR de una venta (vendedor/caja)
        Route::get('pago-qr/venta/{venta}', [PagoController::class, 'generarQrVenta'])->name('pagofacil.generar-qr-venta');
        Route::get('pagofacil/estado-venta/{venta}', [PagoController::class, 'estadoVenta'])->name('pagofacil.estado-venta');

        // CU7 — Créditos y cobranza
        Route::get('creditos', [CreditoController::class, 'index'])->name('creditos.index');
        Route::get('creditos/{credito}', [CreditoController::class, 'show'])->name('creditos.show');
        Route::post('cuotas/{cuota}/pagar', [CreditoController::class, 'pagarCuota'])->name('creditos.pagar-cuota');

        // CU4 — Pedidos (aprobación/rechazo por el vendedor; el despacho lo hace el almacén)
        Route::get('pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::post('pedidos/{pedido}/aprobar', [PedidoController::class, 'aprobar'])->name('pedidos.aprobar');
        Route::post('pedidos/{pedido}/rechazar', [PedidoController::class, 'rechazar'])->name('pedidos.rechazar');
    });

    // Despachos (logística del almacén: almacenero; admin superusuario)
    Route::middleware('rol:admin,almacenero')->group(function () {
        Route::get('despachos', [DespachoController::class, 'index'])->name('despachos.index');
        Route::get('despachos/{venta}', [DespachoController::class, 'show'])->name('despachos.show');
        Route::post('despachos/{venta}/despachar', [DespachoController::class, 'despachar'])->name('despachos.despachar');
    });

    // CU4 (cliente) — Catálogo y mis pedidos
    Route::middleware('rol:cliente')->group(function () {
        Route::get('catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
        Route::post('catalogo/pedido', [CatalogoController::class, 'store'])->name('catalogo.pedido');
        Route::get('mis-pedidos', [MisPedidosController::class, 'index'])->name('mis-pedidos.index');
        Route::get('mis-pedidos/{pedido}', [MisPedidosController::class, 'show'])->name('mis-pedidos.show');
        Route::get('mis-pedidos/{pedido}/pagar-qr', [MisPedidosController::class, 'pagarQr'])->name('mis-pedidos.pagar-qr');
        Route::get('mis-pedidos/{pedido}/estado-pago', [MisPedidosController::class, 'estadoPago'])->name('mis-pedidos.estado-pago');
    });

    // Búsqueda global del negocio (REQ9)
    Route::get('buscar', [BuscarController::class, 'index'])->name('buscar');

    // Notificaciones in-app (todos los roles autenticados)
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/{notificacion}/leida', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leida');
    Route::post('notificaciones/todas', [NotificacionController::class, 'marcarTodas'])->name('notificaciones.todas');

    // Configuración y Bitácora (solo admin)
    Route::middleware('rol:admin')->group(function () {
        Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::put('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    });

    // CU8 — Reportes (admin, vendedor, almacenero)
    Route::middleware('rol:admin,vendedor,almacenero')->group(function () {
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/ventas', [ReporteController::class, 'ventas'])->name('reportes.ventas');
        Route::get('reportes/creditos', [ReporteController::class, 'creditos'])->name('reportes.creditos');
        Route::get('reportes/inventario', [ReporteController::class, 'inventario'])->name('reportes.inventario');
        Route::get('reportes/top-productos', [ReporteController::class, 'topProductos'])->name('reportes.top-productos');
    });

    // Cliente — Mis créditos y Mi cuenta
    Route::middleware('rol:cliente')->group(function () {
        Route::get('mis-creditos', [MisCreditosController::class, 'index'])->name('mis-creditos.index');
        Route::get('mis-creditos/{credito}', [MisCreditosController::class, 'show'])->name('mis-creditos.show');
        Route::post('mis-creditos/{credito}/cuota/{cuota}/pagar', [MisCreditosController::class, 'pagar'])->name('mis-creditos.pagar');
        Route::get('mi-cuenta', [MiCuentaController::class, 'index'])->name('mi-cuenta.index');

    });

    // Pago de cuota por QR (PagoFácil) — accesible para todos los roles autenticados
    // El controlador verifica: admin/vendedor siempre permitido, cliente solo su propio crédito.
    Route::post('pago-qr/cuota/{cuota}', [PagoController::class, 'generarQrCuota'])->name('pagofacil.generar-qr-cuota');
    Route::get('pagofacil/estado-cuota/{cuota}', [PagoController::class, 'estadoCuota'])->name('pagofacil.estado-cuota');
});

// Callback/webhook de PagoFácil (sin auth ni CSRF: lo invoca el servidor de PagoFácil). RN13.
Route::post('webhook/pagofacil-simulado/cuota', [PagoController::class, 'confirmarCuota'])->name('pagofacil.confirmar-cuota');
Route::post('webhook/pagofacil-simulado/venta', [PagoController::class, 'confirmarVenta'])->name('pagofacil.confirmar-venta');
