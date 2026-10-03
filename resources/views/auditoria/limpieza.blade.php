<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bandeja de Limpieza | Auditoría</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Bandeja de Limpieza de Datos (Staging)</h2>
        <p class="text-gray-600 mb-4">Revisa y concilia los levantamientos realizados en campo por los operadores.</p>

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="py-3 px-4 text-left">Fecha</th>
                        <th class="py-3 px-4 text-left">Código</th>
                        <th class="py-3 px-4 text-left">Activo</th>
                        <th class="py-3 px-4 text-left">Ubicación Hallada</th>
                        <th class="py-3 px-4 text-left">Estado</th>
                        <th class="py-3 px-4 text-left">Operador</th>
                        <th class="py-3 px-4 text-center">Acción de Auditoría</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse($pendientes as $censo)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4">{{ $censo->cen_fecha }}</td>
                        <td class="py-3 px-4 font-bold text-blue-600">{{ $censo->act_codigo }}</td>
                        <td class="py-3 px-4">{{ $censo->act_denominacion }}</td>
                        <td class="py-3 px-4">{{ $censo->ubicacion_hallada }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-bold">
                                {{ $censo->estado_hallado }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-sm">{{ $censo->operador }}</td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('auditoria.conciliar') }}" method="POST" class="inline-block">
                                @csrf
                                <input type="hidden" name="cen_id" value="{{ $censo->cen_id }}">
                                <input type="hidden" name="accion" value="APROBADO_SIN_CAMBIOS">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">
                                    Aprobar
                                </button>
                            </form>
                            <form action="{{ route('auditoria.conciliar') }}" method="POST" class="inline-block">
                                @csrf
                                <input type="hidden" name="cen_id" value="{{ $censo->cen_id }}">
                                <input type="hidden" name="accion" value="RECHAZADO">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm ml-1">
                                    Rechazar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-6 px-4 text-center text-gray-500">No hay registros pendientes de limpieza.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $pendientes->links() }}
        </div>
    </div>
</body>
</html>