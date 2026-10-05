@extends('admin.layout')

@section('content')
<div class="max-w-[96%] xl:max-w-7xl mx-auto pb-10 px-2 sm:px-4">

    <!-- ENCABEZADO Y ACCIONES -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-8 gap-5">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Dashboard de Inventario Patrimonial</h1>
            <p class="text-gray-400 text-sm mt-1">Sincronización y consolidación de activos del censo</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 bg-[#1e2330] p-2 rounded-xl border border-gray-800 shadow-sm">
            
            <!-- Selector de Período -->
            <form method="GET" action="{{ route('admin.dashboard') }}" class="m-0 flex items-center">
                <!-- ... tus inputs hidden ... -->
                <select name="periodo" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition cursor-pointer" onchange="this.form.submit()">
                    @foreach($periodos as $per)
                        <option value="{{ $per->per_id }}" {{ $periodoSeleccionado == $per->per_id ? 'selected' : '' }}>
                            Período: {{ $per->per_anio }} {!! $per->per_cerrado ? '(Cerrado)' : '(Activo)' !!}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="w-px h-8 bg-gray-700 hidden sm:block"></div>

            @php
                $periodoActual = $periodos->firstWhere('per_id', $periodoSeleccionado);
            @endphp

            @if($periodoActual && $periodoActual->per_cerrado == 0)
                <!-- Botón Sincronizar -->
                <form action="{{ route('admin.sincronizar') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-md transition flex items-center gap-2" onclick="return confirm('¿Descargar información más reciente desde Google Sheets?')">
                        ☁ Sincronizar Sheets
                    </button>
                </form>

                <!-- Botón Aprobar Lote -->
                <form action="{{ route('admin.aprobarLote') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-md transition flex items-center gap-2" onclick="return confirm('¿Aprobar masivamente todos los registros pendientes?')">
                        ✅ Aprobar Lote
                    </button>
                </form>

                <div class="w-px h-8 bg-gray-700 hidden sm:block"></div>

                <!-- NUEVO: Botón Cerrar Período -->
                <form action="{{ route('admin.cerrarPeriodo', $periodoActual->per_id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-md transition flex items-center gap-2" onclick="return confirm('¿Estás seguro de CERRAR el período {{ $periodoActual->per_anio }}? Esta acción fijará la fecha de cierre hoy y marcará el padrón histórico como definitivo.')">
                        🔒 Cerrar Período
                    </button>
                </form>
            @else
                <!-- Indicador de período cerrado -->
                <span class="bg-gray-800 text-gray-400 border border-gray-700 text-sm font-semibold py-2 px-4 rounded-lg flex items-center gap-2 cursor-not-allowed">
                    🔒 Período Histórico Cerrado el {{ $periodoActual->per_fecha_cierre ?? 'N/A' }}
                </span>
            @endif
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <div class="bg-[#1e2330] border border-gray-800 border-l-4 border-l-blue-500 rounded-xl p-5 shadow-sm">
            <div class="text-xs font-bold text-blue-400 uppercase tracking-wider mb-1">Total Activos (Maestro)</div>
            <div class="text-3xl font-black text-white">{{ $totalActivos }}</div>
        </div>
        <div class="bg-[#1e2330] border border-gray-800 border-l-4 border-l-emerald-500 rounded-xl p-5 shadow-sm">
            <div class="text-xs font-bold text-emerald-400 uppercase tracking-wider mb-1">Censados / Verificados</div>
            <div class="text-3xl font-black text-white mb-2">{{ $verificados }}</div>
            <div class="w-full bg-[#12141c] rounded-full h-1.5 border border-gray-700">
                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $porcentajeAvance }}%"></div>
            </div>
            <div class="text-xs text-gray-400 mt-1.5 font-medium">{{ $porcentajeAvance }}% de Avance</div>
        </div>
        <div class="bg-[#1e2330] border border-gray-800 border-l-4 border-l-amber-500 rounded-xl p-5 shadow-sm">
            <div class="text-xs font-bold text-amber-400 uppercase tracking-wider mb-1">Pendientes (Staging)</div>
            <div class="text-3xl font-black text-white">{{ $pendientes }}</div>
        </div>
        <div class="bg-[#1e2330] border border-gray-800 border-l-4 border-l-red-500 rounded-xl p-5 shadow-sm">
            <div class="text-xs font-bold text-red-400 uppercase tracking-wider mb-1">Alertas (Reubicados)</div>
            <div class="text-3xl font-black text-white">{{ $alertasMovimiento }}</div>
        </div>
    </div>

    <!-- GRÁFICOS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-5 shadow-sm lg:col-span-2">
            <h3 class="text-white font-bold mb-4">Estado del Censo por Ubicación (Top 10)</h3>
            <div class="relative h-64 w-full">
                <canvas id="chartUbicaciones"></canvas>
            </div>
        </div>
        <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-5 shadow-sm">
            <h3 class="text-white font-bold mb-4">Condición Física</h3>
            <div class="relative h-64 w-full">
                <canvas id="chartEstados"></canvas>
            </div>
        </div>
    </div>

    <!-- FILTROS DE TABLA -->
    <div class="bg-[#1e2330] border border-gray-800 rounded-t-xl p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.dashboard') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end m-0">
            <input type="hidden" name="periodo" value="{{ $periodoSeleccionado }}">
            
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wider">Buscar Activo</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Código, denominación..." class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wider">Ubicación</label>
                <select name="ubicacion" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    <option value="">Todas las ubicaciones</option>
                    @foreach($ubicaciones as $ubi)
                        <option value="{{ $ubi->ubi_codigo }}" {{ request('ubicacion') == $ubi->ubi_codigo ? 'selected' : '' }}>
                            {{ $ubi->ubi_nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wider">Estado Físico</label>
                <select name="estado" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $est)
                        <option value="{{ $est->est_nombre }}" {{ request('estado') == $est->est_nombre ? 'selected' : '' }}>
                            {{ $est->est_nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1.5 uppercase tracking-wider">Alerta de Movimiento</label>
                <select name="alerta" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                    <option value="">Todas</option>
                    <option value="SIN VARIACIÓN" {{ request('alerta') == 'SIN VARIACIÓN' ? 'selected' : '' }}>Sin Variación</option>
                    <option value="REUBICADO" {{ request('alerta') == 'REUBICADO' ? 'selected' : '' }}>Reubicado</option>
                    <option value="ALTA NUEVA" {{ request('alerta') == 'ALTA NUEVA' ? 'selected' : '' }}>Alta Nueva</option>
                </select>
            </div>
            <div class="flex gap-2 h-[42px]">
                <button type="submit" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded-lg w-full text-sm font-semibold transition shadow-sm">
                    🔍 Filtrar
                </button>
                <a href="{{ route('admin.dashboard') }}" class="bg-transparent border border-gray-600 hover:bg-[#12141c] text-gray-300 px-4 py-2 rounded-lg w-full text-sm font-semibold transition flex items-center justify-center text-center">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- TABLA DE DATOS -->
    <div class="bg-[#1e2330] border-x border-b border-gray-800 rounded-b-xl shadow-sm overflow-hidden mb-8">
        <div class="overflow-x-auto p-1">
            <table class="w-full text-left text-sm text-gray-300 whitespace-nowrap mt-2">
                <thead class="bg-[#12141c] text-gray-400 border-y border-gray-800">
                    <tr>
                        <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Código</th>
                        <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Denominación</th>
                        <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Marca / Mod</th>
                        <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Tipo / Color</th>
                        <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Ubic. Actual</th>
                        <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Estado</th>
                        <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Constatado</th>
                        <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/80">
                    @forelse($registrosVista as $reg)
                    <tr class="hover:bg-[#2a3040] transition duration-150">
                        <td class="px-5 py-4 font-bold text-white">{{ $reg->codigo_patrimonial }}</td>
                        <td class="px-5 py-4">
                            <span class="block text-white">{{ $reg->denominacion }}</span>
                            @if($reg->alerta_movimiento == 'REUBICADO')
                                <span class="text-[10px] text-red-400 uppercase font-bold tracking-wider">⚠ Reubicado</span>
                            @elseif($reg->alerta_movimiento == 'ALTA NUEVA')
                                <span class="text-[10px] text-blue-400 uppercase font-bold tracking-wider">✦ Nuevo</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-xs">
                            <span class="block text-gray-400">Mrc: <span class="text-gray-200">{{ $reg->marca ?: '-' }}</span></span>
                            <span class="block text-gray-400">Mod: <span class="text-gray-200">{{ $reg->modelo ?: '-' }}</span></span>
                        </td>
                        <td class="px-5 py-4 text-xs">
                            <span class="block text-gray-400">Tipo: <span class="text-gray-200">{{ $reg->tipo ?: '-' }}</span></span>
                            <span class="block text-gray-400">Col: <span class="text-gray-200">{{ $reg->color ?: '-' }}</span></span>
                        </td>
                        <td class="px-5 py-4 truncate max-w-[150px]" title="{{ $reg->ubicacion }}">{{ $reg->ubicacion }}</td>
                        <td class="px-5 py-4">{{ $reg->estado }}</td>
                        <td class="px-5 py-4">
                            @if($reg->constatado_actual == 'CENSADO')
                                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-md text-xs font-semibold">Censado</span>
                            @else
                                <span class="bg-gray-800 text-gray-300 border border-gray-700 px-2.5 py-1 rounded-md text-xs font-semibold">No Localizado</span>
                            @endif
                        </td>
                        <!-- Botón de Edición que activa el Modal Dinámico -->
                        <td class="px-5 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" 
                                    onclick="abrirModalEdicion('{{ $reg->codigo_patrimonial }}', '{{ addslashes($reg->denominacion) }}', '{{ addslashes($reg->marca) }}', '{{ addslashes($reg->modelo) }}', '{{ addslashes($reg->tipo) }}', '{{ addslashes($reg->color) }}', '{{ addslashes($reg->dimensiones) }}', '{{ addslashes($reg->estado) }}', '{{ addslashes($reg->ubicacion) }}')"
                                    class="bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5" title="Editar registro">
                                    ✏️ Editar
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-gray-500">
                            No se encontraron registros para los filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($registrosVista->hasPages())
        <div class="px-5 py-4 border-t border-gray-800 bg-[#161922]">
            {{ $registrosVista->links('pagination::tailwind') }}
        </div>
        @endif
    </div>
</div>

<!-- ================= MODAL FLOTANTE DE EDICIÓN ================= -->
<div id="modalEditar" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-[#161922] border border-gray-800 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden transform transition-all">
        
        <!-- Cabecera del Modal -->
        <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center bg-[#1e2330]">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                ✏️ Editar Activo Patrimonial
            </h3>
            <button type="button" onclick="cerrarModalEdicion()" class="text-gray-400 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <!-- Formulario apuntando a la ruta PUT con directiva @method('PUT') -->
        <form id="formEditarActivo" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            
            <!-- Fila 1: Campos de Texto -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Código Patrimonial</label>
                    <input type="text" id="edit_codigo" name="codigo_patrimonial" readonly class="bg-[#12141c] border border-gray-700 text-gray-500 rounded-lg p-2.5 w-full text-sm cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Denominación</label>
                    <input type="text" id="edit_denominacion" name="denominacion" required class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Dimensiones</label>
                    <input type="text" id="edit_dimensiones" name="dimensiones" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            </div>

            <!-- Fila 2: Selectores Desplegables -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- CAMPO: MARCA -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Marca</label>
                    <select name="mar_id" id="edit_mar_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="">Seleccionar Marca</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca->mar_id }}">{{ $marca->mar_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- CAMPO: MODELO -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Modelo</label>
                    <select name="mod_id" id="edit_mod_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="">Seleccionar Modelo</option>
                        @foreach($modelos as $modelo)
                            <option value="{{ $modelo->mod_id }}">{{ $modelo->mod_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- CAMPO: TIPO (Clasificador) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Tipo</label>
                    <select name="cla_id" id="edit_cla_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="">Seleccionar Tipo</option>
                        @foreach($clasificadores as $clasificador)
                            <option value="{{ $clasificador->cla_id }}">{{ $clasificador->cla_descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- CAMPO: COLOR -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Color</label>
                    <select name="col_id" id="edit_col_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="">Seleccionar Color</option>
                        @foreach($colores as $color)
                            <option value="{{ $color->col_id }}">{{ $color->col_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- CAMPO: ESTADO FÍSICO -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Estado Físico</label>
                    <select name="est_id" id="edit_est_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="">Seleccionar Estado</option>
                        @foreach($estados as $estado)
                            <option value="{{ $estado->est_id }}">{{ $estado->est_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- CAMPO: UBICACIÓN -->
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Ubicación Registrada</label>
                    <select name="ubi_id" id="edit_ubi_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none appearance-none">
                        <option value="">Seleccionar Ubicación</option>
                        @foreach($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->ubi_id }}">{{ $ubicacion->ubi_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-start space-x-3 mt-6 pt-4 border-t border-gray-700">
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium shadow-lg transition">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Script del Modal y Gráficos -->
<script>
    function abrirModalEdicion(codigo, denominacion, marca, modelo, tipo, color, dimensiones, estado, ubicacion) {
        // Asignar dinámicamente la ruta PUT con el código correspondiente
        const urlUpdate = "{{ route('admin.activos.update', ':codigo') }}".replace(':codigo', codigo);
        document.getElementById('formEditarActivo').action = urlUpdate;

        // Rellenar campos simples de texto
        document.getElementById('edit_codigo').value = codigo;
        document.getElementById('edit_denominacion').value = denominacion;
        document.getElementById('edit_dimensiones').value = dimensiones;
        
        // Función auxiliar: Busca la opción en el select basándose en el texto y la selecciona
        function seleccionarPorTexto(selectId, texto) {
            const select = document.getElementById(selectId);
            if (!select || !texto) return;
            for (let i = 0; i < select.options.length; i++) {
                if (select.options[i].text.trim().toUpperCase() === texto.trim().toUpperCase()) {
                    select.selectedIndex = i;
                    break;
                }
            }
        }

        // Llenar los menús desplegables buscando sus textos correctos
        seleccionarPorTexto('edit_mar_id', marca);
        seleccionarPorTexto('edit_mod_id', modelo);
        seleccionarPorTexto('edit_cla_id', tipo);
        seleccionarPorTexto('edit_col_id', color);
        seleccionarPorTexto('edit_est_id', estado);
        seleccionarPorTexto('edit_ubi_id', ubicacion);

        // Mostrar modal
        document.getElementById('modalEditar').classList.remove('hidden');
    }

    function cerrarModalEdicion() {
        document.getElementById('modalEditar').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        Chart.defaults.color = '#9ca3af';
        Chart.defaults.borderColor = '#1f2937';

        const ctxUbi = document.getElementById('chartUbicaciones').getContext('2d');
        new Chart(ctxUbi, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartUbiLabels) !!},
                datasets: [
                    {
                        label: 'Verificados',
                        backgroundColor: '#10b981', 
                        data: {!! json_encode($chartUbiVerificados) !!}
                    },
                    {
                        label: 'No Localizados',
                        backgroundColor: '#374151', 
                        data: {!! json_encode($chartUbiPendientes) !!}
                    }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        const ctxEst = document.getElementById('chartEstados').getContext('2d');
        new Chart(ctxEst, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($chartEstLabels) !!},
                datasets: [{
                    data: {!! json_encode($chartEstData) !!},
                    backgroundColor: ['#10b981', '#ef4444', '#3b82f6', '#f59e0b', '#8b5cf6', '#6b7280'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '70%' }
        });
    });
</script>
@endsection