@extends('reportes._layout')
@section('titulo', 'Reporte de créditos por estado')
@section('contenido')
    <table>
        <thead>
            <tr><th>#</th><th>Venta</th><th>Cliente</th><th>Cuotas</th><th>Interés</th><th>Estado</th><th class="text-end">Saldo</th></tr>
        </thead>
        <tbody>
            @foreach ($creditos as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ $c->venta?->numero_venta }}</td>
                    <td>{{ $c->venta?->cliente?->user?->nombre }} {{ $c->venta?->cliente?->user?->apellidos }}</td>
                    <td>{{ $c->numero_cuotas }}</td>
                    <td>{{ $c->tasa_interes }}%</td>
                    <td>{{ $c->estado }}</td>
                    <td class="text-end">Bs. {{ number_format($c->saldo_pendiente, 2) }}</td>
                </tr>
            @endforeach
            @if ($creditos->isEmpty())
                <tr><td colspan="7" style="text-align:center">Sin créditos.</td></tr>
            @endif
        </tbody>
    </table>
    <div class="totales"><strong>Saldo pendiente total:</strong> Bs. {{ number_format($saldoTotal, 2) }}</div>
@endsection
