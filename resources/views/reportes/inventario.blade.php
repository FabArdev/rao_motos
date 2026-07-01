@extends('reportes._layout')
@section('titulo', 'Inventario crítico (bajo el mínimo)')
@section('contenido')
    <table>
        <thead>
            <tr><th>Código</th><th>Producto</th><th class="text-end">Stock actual</th><th class="text-end">Stock mínimo</th><th class="text-end">Faltante</th></tr>
        </thead>
        <tbody>
            @foreach ($items as $i)
                <tr>
                    <td>{{ $i->producto?->codigo }}</td>
                    <td>{{ $i->producto?->nombre }}</td>
                    <td class="text-end">{{ $i->stock_actual }}</td>
                    <td class="text-end">{{ $i->stock_minimo }}</td>
                    <td class="text-end">{{ max(0, $i->stock_minimo - $i->stock_actual) }}</td>
                </tr>
            @endforeach
            @if ($items->isEmpty())
                <tr><td colspan="5" style="text-align:center">No hay productos bajo el mínimo.</td></tr>
            @endif
        </tbody>
    </table>
@endsection
