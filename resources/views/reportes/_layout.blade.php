<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0; color: #b30000; }
        .sub { color: #666; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; }
        th { background: #f2f2f2; }
        .text-end { text-align: right; }
        .totales { margin-top: 12px; font-size: 12px; }
        .totales strong { display: inline-block; min-width: 140px; }
        .header { border-bottom: 2px solid #b30000; padding-bottom: 6px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏍️ RAO MOTOS</h1>
        <div class="sub">@yield('titulo') · Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
    @yield('contenido')
</body>
</html>
