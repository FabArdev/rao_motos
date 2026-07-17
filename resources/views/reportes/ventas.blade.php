@extends('reportes._layout')
@section('titulo', "Reporte de ventas ($desde a $hasta)")
@section('contenido')
    <table>
        <thead>
            <tr><th>N°</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th>Tipo</th><th>Método</th><th class="text-end">Monto</th></tr>
        </thead>
        <tbody>
            @foreach ($ventas as $v)
                <tr>
                    <td>{{ $v->numero_venta }}</td>
                    <td>{{ $v->fecha->format('d/m/Y') }}</td>
                    <td>{{ $v->cliente?->usuario?->nombre }} {{ $v->cliente?->usuario?->apellidos }}</td>
                    <td>{{ $v->vendedor?->nombre }}</td>
                    <td>{{ $v->tipo_venta }}</td>
                    <td>{{ $v->metodo_pago }}</td>
                    <td class="text-end">Bs. {{ number_format($v->monto_total, 2) }}</td>
                </tr>
            @endforeach
            @if ($ventas->isEmpty())
                <tr><td colspan="7" style="text-align:center">Sin ventas en el rango.</td></tr>
            @endif
        </tbody>
    </table>
    <div class="totales">
        <div><strong>Total contado:</strong> Bs. {{ number_format($contado, 2) }}</div>
        <div><strong>Total crédito:</strong> Bs. {{ number_format($credito, 2) }}</div>
        <div><strong>Total general:</strong> Bs. {{ number_format($total, 2) }}</div>
    </div>
@endsection
