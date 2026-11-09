@extends('admin.layout')

@section('content')

<style>
    [x-cloak] { display: none !important; }
</style>

<!-- Contenedor principal con memoria de pestaña activa -->
<div class="max-w-[96%] xl:max-w-7xl mx-auto pb-10 px-2 sm:px-4 text-gray-100">

    <!-- ENCABEZADO Y ACCIONES GENERALES -->
    <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-6 gap-5">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Dashboard de Inventario Patrimonial</h1>
            <p class="text-gray-400 text-sm mt-1">Sincronización y consolidación de activos del censo</p>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 bg-[#1e2330] p-2 rounded-xl border border-gray-800 shadow-sm">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="m-0 flex items-center">
                @if(request('buscar')) <input type="hidden" name="buscar" value="{{ request('buscar') }}"> @endif
                @if(request('ubicacion')) <input type="hidden" name="ubicacion" value="{{ request('ubicacion') }}"> @endif
                @if(request('estado')) <input type="hidden" name="estado" value="{{ request('estado') }}"> @endif
                @if(request('alerta')) <input type="hidden" name="alerta" value="{{ request('alerta') }}"> @endif

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
                <form action="{{ route('admin.sincronizar') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-md transition flex items-center gap-2" onclick="return confirm('¿Descargar información más reciente desde Google Sheets?')">
                        ☁ Sincronizar Sheets
                    </button>
                </form>

                <form action="{{ route('admin.aprobarLote') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-md transition flex items-center gap-2" onclick="return confirm('¿Aprobar masivamente todos los registros pendientes?')">
                        ✅ Aprobar Lote
                    </button>
                </form>

                <div class="w-px h-8 bg-gray-700 hidden sm:block"></div>

                <form action="{{ route('admin.cerrarPeriodo', $periodoActual->per_id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-md transition flex items-center gap-2" onclick="return confirm('¿Estás seguro de CERRAR el período {{ $periodoActual->per_anio }}? Esta acción fijará la fecha de cierre hoy y marcará el padrón histórico como definitivo.')">
                        🔒 Cerrar Período
                    </button>
                </form>
            @else
                <span class="bg-gray-800 text-gray-400 border border-gray-700 text-sm font-semibold py-2 px-4 rounded-lg flex items-center gap-2 cursor-not-allowed">
                    🔒 Período Histórico Cerrado el {{ $periodoActual->per_fecha_cierre ?? 'N/A' }}
                </span>
            @endif
        </div>
    </div>

    <!-- BOTONES DE PESTAÑAS (RESUMEN / TRAZABILIDAD) -->
    <div class="flex space-x-6 border-b border-gray-800 pb-3 mb-6">
        <button type="button" id="btn-resumen" onclick="cambiarPestana('resumen')" class="border-b-2 border-white font-semibold text-white pb-1 transition focus:outline-none cursor-pointer">
            Resumen
        </button>
        <button type="button" id="btn-trazabilidad" onclick="cambiarPestana('trazabilidad')" class="text-gray-400 hover:text-gray-200 pb-1 transition focus:outline-none cursor-pointer">
            Trazabilidad
        </button>
    </div>

    <!-- PESTAÑA 1: RESUMEN (Solo KPIs y Gráficos) -->
    <div id="contenido-resumen" class="space-y-8" style="display: block;">
        
        <!-- KPIs de Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2 px-2">
            <div>
                <p class="text-[15px] font-semibold text-gray-300">Cobertura de verificación</p>
                <h3 class="text-4xl font-bold text-white mt-1">{{ $porcentajeAvance }}%</h3>
                <p class="text-sm text-gray-500 mt-1">{{ $verificados }} de {{ $totalActivos }} activos</p>
            </div>
            
            <div>
                <p class="text-[15px] font-semibold text-gray-300">Pendientes</p>
                <h3 class="text-4xl font-bold text-white mt-1">{{ $pendientes }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ 100 - $porcentajeAvance }}% restante</p>
            </div>
            
            <div>
                <p class="text-[15px] font-semibold text-gray-300">Deterioro interanual</p>
                <h3 class="text-4xl font-bold text-white mt-1">{{ $deterioro ?? '6' }}%</h3>
                <p class="text-sm text-gray-500 mt-1">bajaron de estado</p>
            </div>
        </div>

        <!-- GRÁFICOS DE RESUMEN -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-transparent border border-gray-800 rounded-xl p-5 shadow-sm lg:row-span-2 flex flex-col">
                <h3 class="text-white font-bold mb-6 text-lg tracking-wide">Avance por ubicación</h3>
                <div class="relative h-[600px] w-full flex-1">
                    <canvas id="chartUbicaciones"></canvas>
                </div>
            </div>

            <div class="flex flex-col gap-6">
                <div class="bg-transparent border border-gray-800 rounded-xl p-5 shadow-sm">
                    <h3 class="text-white font-bold mb-6 text-lg tracking-wide">Activos por estado</h3>
                    <div class="relative h-72 w-full">
                        <canvas id="chartEstados"></canvas>
                    </div>
                </div>

                <div class="bg-transparent border border-gray-800 rounded-xl p-5 shadow-sm">
                    <h3 class="text-white font-bold mb-6 text-lg tracking-wide">Distribución por Tipo de Activo</h3>
                    <div class="relative h-72 w-full">
                        <canvas id="chartTipos"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PESTAÑA 2: TRAZABILIDAD (KPIs de Alertas + Tabla) -->
    <div id="contenido-trazabilidad" class="space-y-6" style="display: none;">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-red-950/40 border border-red-900/50 p-5 rounded-xl shadow-lg">
                <p class="text-sm text-red-400">Activos no encontrados / incidencias</p>
                <h3 class="text-3xl font-bold text-red-200 mt-1">{{ $alertasMovimiento ?? 0 }}</h3>
                <p class="text-xs text-red-400/80 mt-1">crítico — requiere atención en bitácora</p>
            </div>
            <div class="bg-amber-950/40 border border-amber-900/50 p-5 rounded-xl shadow-lg">
                <p class="text-sm text-amber-400">Alertas de movimiento sin resolver</p>
                <h3 class="text-3xl font-bold text-amber-200 mt-1">{{ $pendientes ?? 0 }}</h3>
                <p class="text-xs text-amber-400/80 mt-1">registradas en el periodo actual</p>
            </div>
        </div>

        <!-- BOTÓN NUEVO ACTIVO -->
        <div class="flex justify-end mt-2 mb-2">
            <button type="button" onclick="abrirModalNuevo()" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2 shadow-md">
                ➕ Nuevo Activo
            </button>
        </div>

        <!-- FILTROS DE LA TABLA -->
        <div class="bg-[#1e2330] border border-gray-800 rounded-t-xl p-5 shadow-sm mt-4">
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
                            <option value="{{ $ubi->ubi_nombre }}" {{ request('ubicacion') == $ubi->ubi_nombre ? 'selected' : '' }}>
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
                    <a href="{{ route('admin.dashboard', ['periodo' => $periodoSeleccionado]) }}" class="bg-transparent border border-gray-600 hover:bg-[#12141c] text-gray-300 px-4 py-2 rounded-lg w-full text-sm font-semibold transition flex items-center justify-center text-center">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- TABLA DE DATOS PRINCIPAL -->
        <div class="bg-[#1e2330] border-x border-b border-gray-800 rounded-b-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto p-1">
                <table class="w-full text-left text-sm text-gray-300 whitespace-nowrap mt-2">
                    <thead class="bg-[#12141c] text-gray-400 border-y border-gray-800">
                        <tr>
                            <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Código</th>
                            <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Denominación</th>
                            <th class="px-5 py-3 font-semibold uppercase tracking-wider text-xs">Marca / Serie</th>
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
                                <span class="block text-gray-400">Serie: <span class="text-gray-200">{{ $reg->serie ?: '-' }}</span></span>
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
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button"
                                        onclick='abrirModalEditar(@json($reg))'
                                        class="bg-blue-600/10 hover:bg-blue-600 text-blue-400 hover:text-white border border-blue-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition cursor-pointer">
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
</div>

<!-- ================= MODAL EDITAR ACTIVO PATRIMONIAL COMPLETO ================= -->
<div id="modalEditarActivo" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 hidden items-center justify-center p-4 overflow-y-auto">
    <div class="bg-[#161922] border border-gray-800 rounded-2xl w-full max-w-3xl shadow-2xl overflow-hidden my-8">
        
        <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center bg-[#1e2330]">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                ✏️ Editar Activo Patrimonial
            </h3>
            <button type="button" onclick="cerrarModalEditar()" class="text-gray-400 hover:text-white text-xl font-bold leading-none cursor-pointer">&times;</button>
        </div>

        <form id="formEditarActivo" method="POST" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <!-- FILA 1: Código y Denominación -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Código Patrimonial</label>
                    <input type="text" id="edit_codigo" name="codigo_patrimonial" readonly
                        class="bg-[#12141c] border border-gray-800 text-gray-400 rounded-lg p-2.5 w-full text-sm cursor-not-allowed font-mono">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Denominación *</label>
                    <input type="text" id="edit_denominacion" name="denominacion" required
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                </div>
            </div>

            <!-- FILA 2: Serie y Dimensiones -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Número de Serie</label>
                    <input type="text" id="edit_serie" name="serie" placeholder="SIN SERIE"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Dimensiones</label>
                    <input type="text" id="edit_dimensiones" name="dimensiones" placeholder="SIN DIMENSIONES"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                </div>
            </div>

            <!-- FILA 3: Marca y Modelo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Marca</label>
                    <select id="edit_mar_id" name="mar_id" onchange="filtrarModelosEditar()"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                        <option value="">-- Seleccionar Marca --</option>
                        @foreach($marcas as $m)
                            <option value="{{ $m->mar_id }}" data-nombre="{{ strtoupper(trim($m->mar_nombre)) }}">
                                {{ $m->mar_nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Modelo</label>
                    <select id="edit_mod_id" name="mod_id"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                        <option value="">-- Seleccionar Modelo --</option>
                        @foreach($modelos as $mod)
                            <option value="{{ $mod->mod_id }}" data-mar-id="{{ $mod->mar_id }}" data-nombre="{{ strtoupper(trim($mod->mod_nombre)) }}">
                                {{ $mod->mod_nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- FILA 4: Tipo y Color -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Tipo</label>
                    <select id="edit_cla_id" name="cla_id"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                        <option value="">-- Seleccionar Tipo --</option>
                        @foreach($clasificadores as $c)
                            <option value="{{ $c->cla_id }}" data-nombre="{{ strtoupper(trim($c->cla_descripcion)) }}">
                                {{ $c->cla_descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Color</label>
                    <select id="edit_col_id" name="col_id"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                        <option value="">-- Seleccionar Color --</option>
                        @foreach($colores as $col)
                            <option value="{{ $col->col_id }}" data-nombre="{{ strtoupper(trim($col->col_nombre)) }}">
                                {{ $col->col_nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- FILA 5: Estado Físico y Ubicación -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Estado Físico</label>
                    <select id="edit_est_id" name="est_id"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                        @foreach($estados as $est)
                            <option value="{{ $est->est_id }}" data-nombre="{{ strtoupper(trim($est->est_nombre)) }}">
                                {{ $est->est_nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Ubicación Registrada</label>
                    <select id="edit_ubi_id" name="ubi_id"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                        @foreach($ubicaciones as $ubi)
                            <option value="{{ $ubi->ubi_id }}" data-nombre="{{ strtoupper(trim($ubi->ubi_nombre)) }}">
                                {{ $ubi->ubi_nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- FILA 6: Constatación y Alerta de Movimiento -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t border-gray-800/80">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Estado de Constatación</label>
                    <select id="edit_constatado" name="constatado_actual"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                        <option value="CENSADO">CENSADO</option>
                        <option value="PENDIENTE">PENDIENTE</option>
                        <option value="NO LOCALIZADO">NO LOCALIZADO</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-gray-400 mb-1 uppercase tracking-wider">Alerta de Movimiento</label>
                    <select id="edit_alerta" name="alerta_movimiento"
                        class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:border-blue-500 outline-none">
                        <option value="SIN VARIACIÓN">SIN VARIACIÓN</option>
                        <option value="REUBICADO">REUBICADO</option>
                        <option value="ALTA NUEVA">ALTA NUEVA</option>
                        <option value="NUEVO REGISTRO">NUEVO REGISTRO</option>
                    </select>
                </div>
            </div>

            <!-- FILA 7: Datos Informativos de Auditoría -->
            <div class="bg-[#12141c] border border-gray-800/90 rounded-xl p-3.5 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                <div>
                    <span class="text-gray-500 block uppercase text-[10px] font-semibold">Año de Auditoría</span>
                    <span id="info_anio" class="text-gray-200 font-bold">-</span>
                </div>
                <div>
                    <span class="text-gray-500 block uppercase text-[10px] font-semibold">Fecha Censo</span>
                    <span id="info_fecha" class="text-gray-200 font-semibold">-</span>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <span class="text-gray-500 block uppercase text-[10px] font-semibold">Código Activo</span>
                    <span id="info_id" class="text-blue-400 font-mono">-</span>
                </div>
            </div>

            <div class="flex justify-between items-center pt-4 border-t border-gray-800">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold px-5 py-2.5 rounded-lg text-sm transition shadow-lg cursor-pointer">
                    Guardar Cambios
                </button>
                <button type="button" onclick="cerrarModalEditar()" class="bg-gray-800 hover:bg-gray-700 text-gray-300 px-4 py-2.5 rounded-lg text-sm font-medium transition cursor-pointer">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL FLOTANTE DE NUEVO ACTIVO ================= -->
<div id="modalNuevo" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-[#161922] border border-gray-800 rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden transform transition-all">
        <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center bg-[#1e2330]">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                ➕ Registrar Nuevo Activo
            </h3>
            <button type="button" onclick="cerrarModalNuevo()" class="text-gray-400 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('activos.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Código Patrimonial *</label>
                    <input type="text" name="codigo_patrimonial" required placeholder="Ej. 7408..." class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Denominación *</label>
                    <input type="text" name="denominacion" required placeholder="Ej. SILLA FIJA" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Dimensiones</label>
                    <input type="text" name="dimensiones" placeholder="Opcional" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Serie</label>
                    <input type="text" name="serie" placeholder="Opcional" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Marca</label>
                    <select name="mar_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none appearance-none">
                        <option value="">Seleccionar Marca...</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca->mar_id }}">{{ $marca->mar_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Modelo</label>
                    <select name="mod_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none appearance-none">
                        <option value="">Seleccionar Modelo...</option>
                        @foreach($modelos as $modelo)
                            <option value="{{ $modelo->mod_id }}">{{ $modelo->mod_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Tipo / Clasificador</label>
                    <select name="cla_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none appearance-none">
                        <option value="">Seleccionar Tipo...</option>
                        @foreach($clasificadores as $clasificador)
                            <option value="{{ $clasificador->cla_id }}">{{ $clasificador->cla_descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Color</label>
                    <select name="col_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none appearance-none">
                        <option value="">Seleccionar Color...</option>
                        @foreach($colores as $color)
                            <option value="{{ $color->col_id }}">{{ $color->col_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Estado Físico</label>
                    <select name="est_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none appearance-none">
                        <option value="">Seleccionar Estado...</option>
                        @foreach($estados as $estado)
                            <option value="{{ $estado->est_id }}">{{ $estado->est_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Ubicación Registrada</label>
                    <select name="ubi_id" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-emerald-500 outline-none appearance-none">
                        <option value="">Seleccionar Ubicación...</option>
                        @foreach($ubicaciones as $ubicacion)
                            <option value="{{ $ubicacion->ubi_id }}">{{ $ubicacion->ubi_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="es_temporal" value="1">

                <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-700 col-span-2">
                    <button type="button" onclick="cerrarModalNuevo()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-medium shadow-lg transition">
                        Guardar Activo
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Scripts de Gráficos y Modales -->
<script>
    function cambiarPestana(pestana) {
        const contResumen = document.getElementById('contenido-resumen');
        const contTrazabilidad = document.getElementById('contenido-trazabilidad');
        const btnResumen = document.getElementById('btn-resumen');
        const btnTrazabilidad = document.getElementById('btn-trazabilidad');

        const claseActiva = ['border-b-2', 'border-white', 'font-semibold', 'text-white'];
        const claseInactiva = ['text-gray-400', 'hover:text-gray-200'];

        if (pestana === 'resumen') {
            contResumen.style.display = 'block';
            contTrazabilidad.style.display = 'none';
            
            btnResumen.classList.add(...claseActiva);
            btnResumen.classList.remove(...claseInactiva);
            btnTrazabilidad.classList.remove(...claseActiva);
            btnTrazabilidad.classList.add(...claseInactiva);

            localStorage.setItem('dashboardTab', 'resumen');
        } else {
            contResumen.style.display = 'none';
            contTrazabilidad.style.display = 'block';
            
            btnTrazabilidad.classList.add(...claseActiva);
            btnTrazabilidad.classList.remove(...claseInactiva);
            btnResumen.classList.remove(...claseActiva);
            btnResumen.classList.add(...claseInactiva);

            localStorage.setItem('dashboardTab', 'trazabilidad');
        }
    }

    function seleccionarPorTextoOId(selectId, valorId, valorTexto) {
        const select = document.getElementById(selectId);
        if (!select) return;

        if (valorId !== undefined && valorId !== null && valorId !== '') {
            select.value = valorId;
            if (select.value == valorId) return;
        }

        const textoBuscado = (valorTexto || '').toString().trim().toUpperCase();
        for (let opt of select.options) {
            const nombreOpt = (opt.getAttribute('data-nombre') || opt.text).trim().toUpperCase();
            if (nombreOpt === textoBuscado) {
                select.value = opt.value;
                return;
            }
        }
        select.selectedIndex = 0;
    }

    function filtrarModelosEditar() {
        const marId = document.getElementById('edit_mar_id').value;
        const selectModelo = document.getElementById('edit_mod_id');

        for (let opt of selectModelo.options) {
            if (!opt.value) continue;
            const modMarId = opt.getAttribute('data-mar-id');
            opt.style.display = (!marId || modMarId === marId) ? '' : 'none';
        }
    }

    function abrirModalEditar(reg) {
        const urlUpdate = "{{ route('admin.activos.update', ':codigo') }}".replace(':codigo', reg.codigo_patrimonial);
        document.getElementById('formEditarActivo').action = urlUpdate;

        document.getElementById('edit_codigo').value = reg.codigo_patrimonial || '';
        document.getElementById('edit_denominacion').value = reg.denominacion || '';
        document.getElementById('edit_serie').value = reg.serie || 'SIN SERIE';
        document.getElementById('edit_dimensiones').value = reg.dimensiones || 'SIN DIMENSIONES';

        seleccionarPorTextoOId('edit_mar_id', reg.mar_id, reg.marca);
        filtrarModelosEditar();
        seleccionarPorTextoOId('edit_mod_id', reg.mod_id, reg.modelo);
        seleccionarPorTextoOId('edit_cla_id', reg.cla_id, reg.tipo);
        seleccionarPorTextoOId('edit_col_id', reg.col_id, reg.color);
        seleccionarPorTextoOId('edit_est_id', reg.est_id, reg.estado);
        seleccionarPorTextoOId('edit_ubi_id', reg.ubi_id, reg.ubicacion);

        document.getElementById('edit_constatado').value = (reg.constatado_actual || 'CENSADO').toUpperCase();
        document.getElementById('edit_alerta').value = (reg.alerta_movimiento || 'SIN VARIACIÓN').toUpperCase();

        document.getElementById('info_anio').textContent = reg.anio_auditoria || '{{ $periodoActual->per_anio ?? date("Y") }}';
        document.getElementById('info_fecha').textContent = reg.fecha_censo || 'Registrado';
        document.getElementById('info_id').textContent = reg.codigo_patrimonial || '-';

        const modal = document.getElementById('modalEditarActivo');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function cerrarModalEditar() {
        const modal = document.getElementById('modalEditarActivo');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function abrirModalNuevo() {
        document.getElementById('modalNuevo').classList.remove('hidden');
    }

    function cerrarModalNuevo() {
        document.getElementById('modalNuevo').classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const hayFiltros = urlParams.has('buscar') || 
                           urlParams.has('ubicacion') || 
                           urlParams.has('estado') || 
                           urlParams.has('alerta') || 
                           urlParams.has('page');
        
        let tabGuardada = localStorage.getItem('dashboardTab') || 'resumen';
        
        if (hayFiltros) {
            tabGuardada = 'trazabilidad';
        }
        
        cambiarPestana(tabGuardada);

        Chart.defaults.color = '#9ca3af';
        Chart.defaults.borderColor = '#374151';

        const ctxUbi = document.getElementById('chartUbicaciones');
        if (ctxUbi) {
            new Chart(ctxUbi.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartUbiLabels ?? []) !!},
                    datasets: [
                        { label: 'Verificados', backgroundColor: '#2563eb', data: {!! json_encode($chartUbiVerificados ?? []) !!}, barPercentage: 0.6, categoryPercentage: 0.8 },
                        { label: 'Pendientes', backgroundColor: '#4b5563', data: {!! json_encode($chartUbiPendientes ?? []) !!}, barPercentage: 0.6, categoryPercentage: 0.8 }
                    ]
                },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { stacked: true, grid: { color: '#1f2937' } }, y: { stacked: true, grid: { display: false } } } }
            });
        }

        const ctxEst = document.getElementById('chartEstados');
        if (ctxEst) {
            new Chart(ctxEst.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($chartEstLabels ?? []) !!},
                    datasets: [{ data: {!! json_encode($chartEstData ?? []) !!}, backgroundColor: ['#10b981', '#ef4444', '#3b82f6', '#f59e0b', '#8b5cf6', '#6b7280'], borderWidth: 0 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '70%' }
            });
        }

        const ctxTipos = document.getElementById('chartTipos');
        if (ctxTipos) {
            new Chart(ctxTipos.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($chartTipoLabels ?? []) !!},
                    datasets: [{ label: 'Cantidad', data: {!! json_encode($chartTipoData ?? []) !!}, backgroundColor: ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#14b8a6', '#6366f1'], borderRadius: 4 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#1f2937' }, ticks: { stepSize: 1 } }, x: { grid: { display: false }, ticks: { callback: function(val) { let lbl = this.getLabelForValue(val); return lbl.length > 15 ? lbl.substr(0, 15) + '...' : lbl; } } } } }
            });
        }
    });
</script>
@endsection