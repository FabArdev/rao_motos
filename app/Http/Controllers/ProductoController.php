<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $productos = Producto::with('inventario')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('codigo', 'ilike', "%{$q}%")
                        ->orWhere('marca', 'ilike', "%{$q}%")
                        ->orWhere('modelo', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Productos/Index', [
            'productos' => $productos,
            'filtros' => ['q' => $q],
        ]);
    }

    public function create()
    {
        return Inertia::render('Productos/Create');
    }

    public function store(StoreProductoRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            if ($request->hasFile('foto')) {
                $data['foto_url'] = $request->file('foto')->store('productos', 'public');
            }

            $producto = Producto::create([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'marca' => $data['marca'] ?? null,
                'modelo' => $data['modelo'] ?? null,
                'descripcion' => $data['descripcion'] ?? null,
                'precio_venta_base' => $data['precio_venta_base'],
                'precio_mayorista' => $data['precio_mayorista'],
                'cantidad_minima_mayorista' => $data['cantidad_minima_mayorista'],
                'foto_url' => $data['foto_url'] ?? null,
                'activo' => $data['activo'] ?? true,
            ]);

            // Cada producto nace con su registro de inventario (stock inicial 0).
            Inventario::create([
                'producto_id' => $producto->id,
                'stock_actual' => 0,
                'stock_minimo' => $data['stock_minimo'] ?? 5,
                'tecnica_inventario' => 'PERMANENTE',
                'tecnica_costo' => 'PROMEDIO',
                'fecha_actualizacion' => now(),
            ]);

            // Imágenes adicionales de la galería.
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $i => $file) {
                    ProductoImagen::create([
                        'producto_id' => $producto->id,
                        'ruta' => $file->store('productos', 'public'),
                        'orden' => $i,
                    ]);
                }
            }
        });

        return redirect()->route('productos.index')->with('success', 'Producto creado correctamente.');
    }

    public function show(Producto $producto)
    {
        $producto->load('inventario', 'imagenes');

        return Inertia::render('Productos/Show', ['producto' => $producto]);
    }

    public function edit(Producto $producto)
    {
        $producto->load('inventario', 'imagenes');

        return Inertia::render('Productos/Edit', ['producto' => $producto]);
    }

    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $producto) {
            if ($request->hasFile('foto')) {
                if ($producto->foto_url) {
                    Storage::disk('public')->delete($producto->foto_url);
                }
                $producto->foto_url = $request->file('foto')->store('productos', 'public');
            }

            $producto->fill([
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'marca' => $data['marca'] ?? null,
                'modelo' => $data['modelo'] ?? null,
                'descripcion' => $data['descripcion'] ?? null,
                'precio_venta_base' => $data['precio_venta_base'],
                'precio_mayorista' => $data['precio_mayorista'],
                'cantidad_minima_mayorista' => $data['cantidad_minima_mayorista'],
                'activo' => $data['activo'] ?? $producto->activo,
            ])->save();

            if (isset($data['stock_minimo']) && $producto->inventario) {
                $producto->inventario->update(['stock_minimo' => $data['stock_minimo']]);
            }

            // Eliminar imágenes de galería marcadas.
            if (! empty($data['eliminar_imagenes'])) {
                $aBorrar = ProductoImagen::where('producto_id', $producto->id)
                    ->whereIn('id', $data['eliminar_imagenes'])->get();
                foreach ($aBorrar as $img) {
                    Storage::disk('public')->delete($img->ruta);
                    $img->delete();
                }
            }

            // Agregar nuevas imágenes de galería.
            if ($request->hasFile('imagenes')) {
                $orden = (int) ProductoImagen::where('producto_id', $producto->id)->max('orden');
                foreach ($request->file('imagenes') as $file) {
                    ProductoImagen::create([
                        'producto_id' => $producto->id,
                        'ruta' => $file->store('productos', 'public'),
                        'orden' => ++$orden,
                    ]);
                }
            }
        });

        return redirect()->route('productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        // Eliminación lógica: el producto puede estar referenciado por ventas/compras.
        $producto->update(['activo' => false]);

        return redirect()->route('productos.index')->with('success', 'Producto desactivado.');
    }
}
