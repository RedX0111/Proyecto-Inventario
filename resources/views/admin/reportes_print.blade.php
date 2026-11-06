<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Institucional de Inventario Patrimonial</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 3px 0; font-size: 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .footer { margin-top: 20px; text-align: right; font-size: 10px; color: #666; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="background: #1e2330; padding: 15px; margin-bottom: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; color: white;">
        <div>
            <strong>Vista previa del Reporte</strong> — Total de registros encontrados: {{ count($registros) }}
        </div>
        <button onclick="window.print()" style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ Imprimir / Guardar PDF
        </button>
    </div>

    <div class="header">
        <h2>Reporte General de Inventario Patrimonial</h2>
        <p>Sistema de Control y Sincronización de Activos</p>
        <p>Fecha de emisión: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                @foreach($columnasSeleccionadas as $col)
                    <th>{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $index => $reg)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    @foreach($columnasSeleccionadas as $col)
                        <td>{{ $reg->{$col} ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columnasSeleccionadas) + 1 }}" style="text-align: center; padding: 20px;">No se encontraron registros con los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Generado automáticamente por el Sistema Patrimonial</p>
    </div>

</body>
</html>
