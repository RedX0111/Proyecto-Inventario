<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Padrón Maestro | Auditoría</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-2 text-gray-800">Padrón Maestro Institucional</h2>
        
        <form method="GET" action="{{ route('auditoria.padron') }}" class="mb-6 flex gap-2 mt-4">
            <input type="text" name="buscar" placeholder="Buscar por código o denominación..." class="border border-gray-300 rounded px-4 py-2 w-1/3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Buscar</button>
            <a href="{{ route('auditoria.padron') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Limpiar</a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border text-sm">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-3 px-3 text-left">Código</th>
                        <th class="py-3 px-3 text-left">Activo</th>
                        <th class="py-3 px-3 text-left">Ubicación Actual</th>
                        <th class="py-3 px-3 text-left">Estado Físico</th>
                        <th class="py-3 px-3 text-center">Hallazgo (Censo)</th>
                        <th class="py-3 px-3 text-center">Alerta de Movimiento</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse($activos as $activo)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-3 font-bold">{{ $activo->codigo_patrimonial }}</td>
                        <td class="py-3 px-3">{{ $activo->denominacion }}</td>
                        <td class="py-3 px-3">{{ $activo->ubicacion }}</td>
                        <td class="py-3 px-3">{{ $activo->estado }}</td>
                        <td class="py-3 px-3 text-center">
                            @if($activo->constatado_actual == 'CENSADO')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded font-bold">CENSADO</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded font-bold">NO LOCALIZADO</span>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-center">
                            @if($activo->alerta_movimiento == 'REUBICADO')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded font-bold">REUBICADO</span>
                            @elseif($activo->alerta_movimiento == 'ALTA NUEVA')
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded font-bold">ALTA NUEVA</span>
                            @else
                                <span class="px-2 py-1 text-gray-500">{{ $activo->alerta_movimiento }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-6 px-4 text-center text-gray-500">No se encontraron activos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $activos->links() }}
        </div>
    </div>
</body>
</html>