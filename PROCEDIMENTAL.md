# Diagrama Procedimental — RAO MOTOS (Aplicación Web)

Este documento traza, **paso a paso**, el flujo principal de cada Caso de Uso (CU):
quién lo dispara, en qué **archivo** entra, con qué **variables/datos**, qué
**funciones** llama y a qué **clase/capa** lo lleva, hasta que el CU se cumple.

## Cómo leerlo

La app es **Laravel + Inertia + Vue**. La flecha `→` significa "llama a / lleva a".
Las capas involucradas:

```
resources/js/Pages/*.vue     → interfaz (Inertia): formularios y botones del usuario
routes/web.php               → define la URL y el middleware (auth + rol:<roles>)
app/Http/Controllers/*.php   → recibe la petición, orquesta y responde con Inertia::render / redirect
app/Http/Requests/Store*.php → valida los datos de entrada (Form Request)
app/Services/*.php           → reglas de negocio (VentaService, CreditoService, InventarioService, PagoFacilService)
app/Models/*.php             → Eloquent (una clase por tabla): Usuario, Producto, Venta, Credito, ...
Base de datos (PostgreSQL)
```

**Flujo común a TODOS los CU (entrada y salida):**

```
Página Vue (resources/js/Pages/...)
  → useForm().post(...) | router.post|get(route('nombre.ruta'), datos)     [Inertia]
  → routes/web.php  (middleware: auth + rol:<roles>)  → <XxxController>@<accion>(Request/Modelo)
        · Http/Requests/Store*Request valida los datos ($request->validated())
        → App\Services\*Service   (reglas de negocio)
        → App\Models\*            (Eloquent → tablas)
  → redirect()->route(...) | back()     → Inertia re-renderiza la Página Vue     ✅
```

En los CU de abajo se detalla desde la **Página Vue** hasta la respuesta.

---

## CU1 — Usuarios y Roles · flujo principal: crear usuario

**Actor:** Admin

1. **`resources/js/Pages/Usuarios/Create.vue`**
   - `useForm({ nombre, apellidos, ci, telefono, direccion, correo, password, rol_id, nit_ci, estado })`
   - → `form.post(route('usuarios.store'))`
2. **`routes/web.php`** (`middleware('rol:admin')`) → `Route::resource('usuarios')` → **`UsuarioController@store`**
3. **`app/Http/Controllers/UsuarioController.php`** → `store(StoreUsuarioRequest $request)`
   - valida con **`app/Http/Requests/StoreUsuarioRequest`** → `$data = $request->validated()`
   - `DB::transaction(...)`:
     - → `Usuario::create([... 'password' => Hash::make($data['password']), 'rol_id' => ...])`
     - si el rol es cliente → `Cliente::updateOrCreate(['id' => $usuario->id], ['nit_ci' => ...])`
4. **`app/Models/Usuario.php` / `Cliente.php`** (Eloquent) → tablas `usuario` / `cliente`
5. **✅ CU cumplido:** `redirect()->route('usuarios.index')` → Inertia re-renderiza **`Pages/Usuarios/Index.vue`** con el usuario creado.

---

## CU2 — Productos · flujo principal: crear producto

**Actor:** Admin / Almacenero

1. **`resources/js/Pages/Productos/Create.vue`**
   - `useForm({ codigo, nombre, marca, modelo, descripcion, precio_venta_base, precio_mayorista, cantidad_minima_mayorista, foto, imagenes[], stock_minimo, activo })`
   - → `form.post(route('productos.store'))` (multipart, con imágenes)
2. **`routes/web.php`** (`rol:admin,almacenero`) → `Route::resource('productos')` → **`ProductoController@store`**
3. **`ProductoController@store(StoreProductoRequest $request)`**
   - `$data = $request->validated()`; `DB::transaction(...)`:
     - si hay foto → `$request->file('foto')->store('productos','public')`
     - → `Producto::create([...])`
     - → `Inventario::create([producto_id, stock_actual: 0, stock_minimo, tecnica_inventario: 'PERMANENTE', tecnica_costo: 'PROMEDIO'])`
     - por cada imagen → `ProductoImagen::create([...])`
4. **`app/Models/Producto.php` / `Inventario.php` / `ProductoImagen.php`** → tablas
5. **✅ CU cumplido:** `redirect()->route('productos.index')` → Inertia re-renderiza **`Pages/Productos/Index.vue`**.

> El producto nace con **inventario en 0**; el stock entra al recibir compras (CU3) o por ajuste (CU5).

---

## CU3 — Compras · flujo principal: recibir compra (ingreso de stock)

**Actor:** Admin / Almacenero *(antes se creó la compra `PENDIENTE` con sus detalles)*

1. **`resources/js/Pages/Compras/Show.vue`** → botón "Recibir" → `router.post(route('compras.recibir', compra.id))`
2. **`routes/web.php`** (`rol:admin,almacenero`) → `Route::post('compras/{compra}/recibir', ...)` → **`CompraController@recibir`**
3. **`CompraController@recibir(Compra $compra)`**
   - valida `estado === 'PENDIENTE'`; `DB::transaction(...)`:
     - `$compra->load('detalles')`
     - lee márgenes → `Configuracion::valor('margen_venta_minorista'/'..._mayorista')`
     - por cada detalle `$d`:
       - → `App\Services\InventarioService->ingreso($d->producto_id, $d->cantidad, "Compra #.. recibida")`
       - → `Producto::whereKey($d->producto_id)->update(['precio_venta_base' => costo×(1+margenMin%), 'precio_mayorista' => costo×(1+margenMay%)])`
     - `$compra->update(['estado' => 'RECIBIDA'])`
4. **`app/Services/InventarioService.php`** → `ingreso(...)` actualiza el modelo **`Inventario`** (stock) y registra un **`MovimientoInventario`**
5. **✅ CU cumplido:** stock ingresado y precios recalculados; `back()->with('success', ...)` → Inertia re-renderiza la vista de la compra.

---

## CU4 — Pedidos · flujo principal: el cliente arma su pedido (Catálogo)

**Actor:** Cliente

1. **`resources/js/Pages/Catalogo/Index.vue`** (+ **`Components/Cart/*`**)
   - arma el carrito `items: [{ producto_id, cantidad }]`
   - → `router.post(route('catalogo.pedido'), { items })`
2. **`routes/web.php`** (`rol:cliente`) → `Route::post('catalogo/pedido', ...)` → **`CatalogoController@store`**
3. **`CatalogoController@store(Request $request)`**
   - `$request->validate(['items' => required|array, 'items.*.producto_id' => exists:producto,id, 'items.*.cantidad' => min:1])`
   - `DB::transaction(...)`:
     - → `Pedido::create(['cliente_id' => $request->user()->id, 'estado' => 'SOLICITADO'])`
     - por ítem → `DetallePedido::create([...])`
     - notifica a los vendedores → `Usuario::whereHas('rol', 'vendedor')` y por cada uno `Notificacion::create(['tipo' => 'PEDIDO_POR_APROBAR', ...])`
4. **`app/Models/Pedido.php` / `DetallePedido.php` / `Notificacion.php` / `Usuario.php`** → tablas
5. **✅ CU cumplido:** `redirect()->route('mis-pedidos.show', $pedido->id)` → Inertia re-renderiza **`Pages/MisPedidos/Show.vue`**.

> Continuación (no principal): un vendedor lo aprueba (`PedidoController@aprobar` → genera venta) o lo rechaza; luego se cobra y se despacha (`DespachoController`).

---

## CU5 — Inventario · flujo principal: ajuste de stock

**Actor:** Admin / Almacenero

1. **`resources/js/Pages/Inventario/Show.vue`**
   - `useForm({ cantidad, motivo })` (cantidad positiva = ingreso, negativa = egreso)
   - → `form.post(route('inventario.ajuste', inventario.id))`
2. **`routes/web.php`** (`rol:admin,almacenero`) → `Route::post('inventario/{inventario}/ajuste', ...)` → **`InventarioController@ajuste`**
3. **`InventarioController@ajuste(Request $request, Inventario $inventario)`**
   - `$request->validate(['cantidad' => integer|not_in:0, 'motivo' => required|string])`
   - `DB::transaction(...)`:
     - si `cantidad > 0` → `App\Services\InventarioService->ingreso($inventario->producto_id, cantidad, motivo)`
     - si `cantidad < 0` → `App\Services\InventarioService->egreso($inventario->producto_id, abs(cantidad), motivo)`
4. **`app/Services/InventarioService.php`** → actualiza **`Inventario`** (stock) y registra **`MovimientoInventario`** (INGRESO/EGRESO)
5. **✅ CU cumplido:** `redirect()->route('inventario.index')` → Inertia re-renderiza **`Pages/Inventario/Index.vue`**.

---

## CU6 — Ventas · flujo principal: registrar venta

**Actor:** Admin / Vendedor

1. **`resources/js/Pages/Ventas/Create.vue`**
   - `useForm({ cliente_id, tipo_venta (CONTADO|CREDITO), metodo_pago (EFECTIVO|QR), items: [{producto_id, cantidad}], numero_cuotas, tasa_interes })`
   - → `form.post(route('ventas.store'))`
2. **`routes/web.php`** (`rol:admin,vendedor`) → `Route::resource('ventas')->only([..., 'store'])` → **`VentaController@store`**
3. **`VentaController@store(StoreVentaRequest $request)`**
   - `$data = $request->validated()`; `DB::transaction(...)`:
     - calcula `estado` (CONTADO+EFECTIVO → COMPLETADA; CREDITO → COMPLETADA; CONTADO+QR → PENDIENTE)
     - → `App\Services\VentaService->crear(['cliente_id', 'vendedor_id' => $request->user()->id, 'tipo_venta', 'metodo_pago', 'estado', 'descontar_stock' => true, 'items'])`
     - si `tipo_venta === 'CREDITO'` → `App\Services\CreditoService->generar($venta, $numero_cuotas, $tasa_interes)`
4. **`app/Services/VentaService.php`** → `crear($data)`
   - usa **`InventarioService`** (`verificarStock` + `egreso` por línea), calcula el total y el `numero_venta`
   - → `Venta::create([...])` + `DetalleVenta::create([...])` por ítem
   - (crédito) `CreditoService->generar` → `Credito::create` + N `PagoCuota::create` (calendario de cuotas)
5. **`app/Models/Venta.php` / `DetalleVenta.php` / `Credito.php` / `PagoCuota.php`** → tablas
6. **✅ CU cumplido:**
   - si CONTADO + QR + PENDIENTE → `redirect()->route('pagofacil.generar-qr-venta', $venta->id)` (→ **`PagoController@generarQrVenta`** → **`PagoFacilService->generarQRVentaSimulado`**)
   - si no → `redirect()->route('ventas.show', $venta->id)` → Inertia **`Pages/Ventas/Show.vue`**.

---

## CU7 — Créditos y Pagos · flujo principal: pagar una cuota

**Actor:** Vendedor (efectivo) · Cliente (QR)

1. **`resources/js/Pages/Creditos/Show.vue`** (vendedor)
   - `router.post(route('creditos.pagar-cuota', cuota.id), { metodo_pago_id })`
   - *(alternativa cliente por QR: `Pages/MisCreditos/Show.vue` → `route('pagofacil.generar-qr-cuota', cuota.id)`)*
2. **`routes/web.php`** (`rol:admin,vendedor`) → `Route::post('cuotas/{cuota}/pagar', ...)` → **`CreditoController@pagarCuota`**
3. **`CreditoController@pagarCuota(Request $request, PagoCuota $cuota)`**
   - `$request->validate(['metodo_pago_id' => nullable|exists])`; si `$cuota->estado === 'PAGADO'` → error
   - → `App\Services\CreditoService->registrarPagoCuota($cuota, $data['metodo_pago_id'])`
4. **`app/Services/CreditoService.php`** → `registrarPagoCuota($cuota, $metodoId)`
   - calcula la mora con `calcularMora($cuota)`, marca la **`PagoCuota`** como `PAGADO`, descuenta el `saldo_pendiente` del **`Credito`**; si ya no quedan cuotas → crédito `PAGADO`
5. **`app/Models/PagoCuota.php` / `Credito.php`** → tablas
6. **Camino QR (asíncrono):** `PagoController@generarQrCuota` → `PagoFacilService->generarQRCuotaSimulado`; la confirmación llega por webhook `Route::post('webhook/pagofacil-simulado/cuota', [PagoController, 'confirmarCuota'])` → `CreditoService->registrarPagoCuota`. Además `CreditoController@show` reconcilia con `PagoFacilService->verificarEstadoPago`.
7. **✅ CU cumplido:** `back()->with('success', ...)` → Inertia re-renderiza la vista del crédito con la cuota pagada.
