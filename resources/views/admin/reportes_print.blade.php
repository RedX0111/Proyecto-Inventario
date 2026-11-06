<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Institucional de Inventario Patrimonial</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; margin: 20px; background-color: #fff; }
        
        /* Barra superior interactiva (oculta al imprimir) */
        .no-print { background: #1e2330; padding: 15px; margin-bottom: 25px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; color: white; }
        .no-print button { background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .no-print button:hover { background: #1d4ed8; }

        /* Estructura del Encabezado de 3 columnas */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 12px; }
        .header-table td { border: none; padding: 0; vertical-align: middle; }
        
        .header-left { width: 20%; text-align: left; }
        .header-left img { max-height: 60px; max-width: 120px; object-fit: contain; }

        .header-center { width: 60%; text-align: center; }
        .header-center h2 { margin: 0; font-size: 15px; text-transform: uppercase; font-weight: bold; color: #111; }
        .header-center p { margin: 4px 0 0 0; font-size: 11px; color: #555; }

        .header-right { width: 20%; text-align: right; font-size: 10px; color: #444; line-height: 1.4; }

        /* Estilos de la tabla de datos */
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #bbb; padding: 6px 8px; text-align: left; }
        table.data-table th { background-color: #f1f5f9; font-weight: bold; font-size: 10px; text-transform: uppercase; color: #1e293b; }
        table.data-table tr:nth-child(even) { background-color: #f8fafc; }

        .footer { margin-top: 25px; text-align: right; font-size: 10px; color: #666; border-top: 1px solid #ddd; pt: 8px; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 10px; }
            @page { size: auto; margin: 15mm; }
        }
    </style>
</head>
<body>

    <!-- Panel de control interactivo (Solo pantalla) -->
    <div class="no-print">
        <div>
            <strong>Vista previa del Reporte</strong> — Total de registros: <strong>{{ count($registros) }}</strong>
        </div>
        <button onclick="window.print()">
            🖨️ Imprimir / Guardar PDF
        </button>
    </div>

    <!-- Encabezado Institucional (3 Columnas) -->
    <table class="header-table">
        <tr>
            <!-- Columna Izquierda: Logotipo -->
            <td class="header-left">
                <!-- Reemplaza la URL o usa asset('images/tu-logo.png') según prefieras -->
                <img src="{{ asset('images/logo.png') }}" alt="Logo Institucional" onerror="this.src='https://itel.unjbg.edu.pe/portal-web/itel/section/f94c2d90-a123-4c5d-9614-4dec12a82a13.png'">
            </td>

            <!-- Columna Central: Títulos -->
            <td class="header-center">
                <h2>Reporte General de Inventario Patrimonial</h2>
                <p>Sistema de Control y Sincronización de Activos — Padrón Oficial</p>
            </td>

            <!-- Columna Derecha: Fecha y Emisión -->
            <td class="header-right">
                <strong>Fecha de Emisión:</strong><br>
                {{ date('d/m/Y') }}<br>
                {{ date('H:i') }} hrs.
            </td>
        </tr>
    </table>

    <!-- Tabla Principal de Registros -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px; text-align: center;">#</th>
                @foreach($columnasSeleccionadas as $col)
                    <th>{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($registros as $index => $reg)
                <tr>
                    <td style="text-align: center; color: #555;">{{ $index + 1 }}</td>
                    @foreach($columnasSeleccionadas as $col)
                        <td>{{ $reg->{$col} ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columnasSeleccionadas) + 1 }}" style="text-align: center; padding: 25px; color: #666;">
                        No se encontraron registros con los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pie de página institucional -->
    <div class="footer">
        <p>Generado automáticamente por el Sistema de Inventario Patrimonial — Universidad Nacional Jorge Basadre Grohmann</p>
    </div>

</body>
</html>