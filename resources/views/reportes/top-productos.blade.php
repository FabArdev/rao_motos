@extends('reportes._layout')
@section('titulo', 'Productos más vendidos')
@section('contenido')
    <table>
        <thead>
            <tr><th>#</th><th>Código</th><th>Producto</th><th class="text-end">Unidades</th><th class="text-end">Monto</th></tr>
        </thead>
        <tbody>
            @foreach ($items as $idx => $i)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $i->producto?->codigo }}</td>
                    <td>{{ $i->producto?->nombre }}</td>
                    <td class="text-end">{{ $i->cantidad }}</td>
                    <td class="text-end">Bs. {{ number_format($i->monto, 2) }}</td>
                </tr>
            @endforeach
            @if ($items->isEmpty())
                <tr><td colspan="5" style="text-align:center">Sin ventas registradas.</td></tr>
            @endif
        </tbody>
    </table>
@endsection
