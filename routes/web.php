<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\MisPedidosController;
use App\Http\Controllers\TallerController;
use App\Http\Controllers\MiTallerController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\MisCreditosController;
use App\Http\Controllers\MiCuentaController;
use App\Http\Controllers\BuscarController;
use App\Http\Controllers\PagoController;

/*
|--------------------------------------------------------------------------
| Web Routes — RAO MOTOS
|--------------------------------------------------------------------------
| Las rutas de autenticación las registra Jetstream/Fortify.
| Las rutas de negocio se agregan por CU (usuarios, productos, ventas, taller, ...).
*/

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
    Route::middleware('role:admin')->group(function () {
        Route::resource('usuarios', UserController::class)->parameters(['usuarios' => 'usuario']);
    });

    // CU2 — Gestión de productos / CU3 Proveedores·Compras / CU5 Inventario (almacenero; admin superusuario)
    Route::middleware('role:admin,almacenero')->group(function () {
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
    Route::middleware('role:admin,vendedor')->group(function () {
        Route::post('ventas/{venta}/anular', [VentaController::class, 'anular'])->name('ventas.anular');
        Route::resource('ventas', VentaController::class)->parameters(['ventas' => 'venta'])->only(['index', 'create', 'store', 'show']);

        // CU7 — Créditos y cobranza
        Route::get('creditos', [CreditoController::class, 'index'])->name('creditos.index');
        Route::get('creditos/{credito}', [CreditoController::class, 'show'])->name('creditos.show');
        Route::post('cuotas/{cuota}/pagar', [CreditoController::class, 'pagarCuota'])->name('creditos.pagar-cuota');

        // CU4 — Pedidos (gestión por el vendedor)
        Route::get('pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
        Route::get('pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
        Route::post('pedidos/{pedido}/aprobar', [PedidoController::class, 'aprobar'])->name('pedidos.aprobar');
        Route::post('pedidos/{pedido}/rechazar', [PedidoController::class, 'rechazar'])->name('pedidos.rechazar');
        Route::post('pedidos/{pedido}/despachar', [PedidoController::class, 'despachar'])->name('pedidos.despachar');
    });

    // CU4 (cliente) — Catálogo y mis pedidos
    Route::middleware('role:cliente')->group(function () {
        Route::get('catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
        Route::post('catalogo/pedido', [CatalogoController::class, 'store'])->name('catalogo.pedido');
        Route::get('mis-pedidos', [MisPedidosController::class, 'index'])->name('mis-pedidos.index');
        Route::get('mis-pedidos/{pedido}', [MisPedidosController::class, 'show'])->name('mis-pedidos.show');
    });

    // CU9 — Taller
    Route::middleware('role:admin,vendedor,almacenero,mecanico')->group(function () {
        Route::get('taller', [TallerController::class, 'index'])->name('taller.index');
        Route::get('taller/{orden}', [TallerController::class, 'show'])->name('taller.show');
    });
    Route::middleware('role:admin,mecanico')->group(function () {
        Route::get('taller-nueva/create', [TallerController::class, 'create'])->name('taller.create');
        Route::post('taller', [TallerController::class, 'store'])->name('taller.store');
        Route::post('taller/{orden}/diagnosticar', [TallerController::class, 'diagnosticar'])->name('taller.diagnosticar');
        Route::post('taller/{orden}/solicitar-repuestos', [TallerController::class, 'solicitarRepuestos'])->name('taller.solicitar-repuestos');
        Route::post('taller/{orden}/terminar', [TallerController::class, 'terminar'])->name('taller.terminar');
    });
    Route::middleware('role:admin,almacenero')->group(function () {
        Route::post('taller-repuesto/{detalle}/aprobar', [TallerController::class, 'aprobarRepuesto'])->name('taller.aprobar-repuesto');
        Route::post('taller-repuesto/{detalle}/rechazar', [TallerController::class, 'rechazarRepuesto'])->name('taller.rechazar-repuesto');
    });
    Route::middleware('role:admin,vendedor')->group(function () {
        Route::post('taller/{orden}/facturar', [TallerController::class, 'facturar'])->name('taller.facturar');
        Route::post('taller/{orden}/entregar', [TallerController::class, 'entregar'])->name('taller.entregar');
    });

    // CU9 (cliente) — Mi taller
    Route::middleware('role:cliente')->group(function () {
        Route::get('mi-taller', [MiTallerController::class, 'index'])->name('mi-taller.index');
        Route::get('mi-taller/{orden}', [MiTallerController::class, 'show'])->name('mi-taller.show');
        Route::post('mi-taller/{orden}/aprobar-presupuesto', [MiTallerController::class, 'aprobarPresupuesto'])->name('mi-taller.aprobar-presupuesto');
        Route::post('mi-taller/{orden}/rechazar-presupuesto', [MiTallerController::class, 'rechazarPresupuesto'])->name('mi-taller.rechazar-presupuesto');
    });

    // Búsqueda global del negocio (REQ9)
    Route::get('buscar', [BuscarController::class, 'index'])->name('buscar');

    // Notificaciones in-app (todos los roles autenticados)
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/{notificacion}/leida', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leida');
    Route::post('notificaciones/todas', [NotificacionController::class, 'marcarTodas'])->name('notificaciones.todas');

    // Configuración y Bitácora (solo admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::put('configuracion', [ConfiguracionController::class, 'update'])->name('configuracion.update');
        Route::get('bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    });

    // CU8 — Reportes (admin, vendedor, almacenero)
    Route::middleware('role:admin,vendedor,almacenero')->group(function () {
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/ventas', [ReporteController::class, 'ventas'])->name('reportes.ventas');
        Route::get('reportes/creditos', [ReporteController::class, 'creditos'])->name('reportes.creditos');
        Route::get('reportes/inventario', [ReporteController::class, 'inventario'])->name('reportes.inventario');
        Route::get('reportes/top-productos', [ReporteController::class, 'topProductos'])->name('reportes.top-productos');
    });

    // Cliente — Mis créditos y Mi cuenta
    Route::middleware('role:cliente')->group(function () {
        Route::get('mis-creditos', [MisCreditosController::class, 'index'])->name('mis-creditos.index');
        Route::get('mis-creditos/{credito}', [MisCreditosController::class, 'show'])->name('mis-creditos.show');
        Route::post('mis-creditos/{credito}/cuota/{cuota}/pagar', [MisCreditosController::class, 'pagar'])->name('mis-creditos.pagar');
        Route::get('mi-cuenta', [MiCuentaController::class, 'index'])->name('mi-cuenta.index');

    });

    // Pago de cuota por QR (PagoFácil) — accesible para todos los roles autenticados
    // El controlador verifica: admin/vendedor siempre permitido, cliente solo su propio crédito.
    Route::post('pago-qr/cuota/{cuota}', [PagoController::class, 'generarQrCuota'])->name('pagofacil.generar-qr-cuota');
});

// Callback/webhook de PagoFácil (sin auth ni CSRF: lo invoca el servidor de PagoFácil). RN13.
Route::post('webhook/pagofacil-simulado/cuota', [PagoController::class, 'confirmarCuota'])->name('pagofacil.confirmar-cuota');
