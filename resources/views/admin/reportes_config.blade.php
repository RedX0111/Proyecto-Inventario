@extends('admin.layout')

@section('content')
<div class="max-w-4xl mx-auto pb-10 px-4 text-gray-100">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white tracking-tight">🖨️ Generador Avanzado de Reportes</h1>
        <p class="text-gray-400 text-sm mt-1">Selecciona los criterios, filtros y columnas que deseas incluir en tu reporte institucional.</p>
    </div>

    <form action="{{ route('admin.reportes.generar') }}" method="POST" target="_blank" class="bg-[#1e2330] border border-gray-800 rounded-2xl p-6 shadow-xl space-y-6">
        @csrf

        <!-- SECCIÓN 1: FILTROS -->
        <div>
            <h3 class="text-white font-semibold text-base mb-3 border-b border-gray-700 pb-2">1. Filtros de Búsqueda</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Período Fiscal</label>
                    <select name="periodo" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm outline-none">
                        <option value="">Todos los períodos</option>
                        @foreach($periodos as $p)
                            <option value="{{ $p->per_id }}">Año {{ $p->per_anio }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Ubicación</label>
                    <select name="ubicacion" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm outline-none">
                        <option value="">Todas las ubicaciones</option>
                        @foreach($ubicaciones as $u)
                            <option value="{{ $u->ubi_nombre }}">{{ $u->ubi_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Estado Físico</label>
                    <select name="estado" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm outline-none">
                        <option value="">Todos los estados</option>
                        @foreach($estados as $e)
                            <option value="{{ $e->est_nombre }}">{{ $e->est_nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Tipo de Activo</label>
                    <select name="tipo" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm outline-none">
                        <option value="">Todos los tipos</option>
                        @foreach($clasificadores as $c)
                            <option value="{{ $c->cla_descripcion }}">{{ $c->cla_descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Constatación</label>
                    <select name="constatado" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm outline-none">
                        <option value="">Todos</option>
                        <option value="CENSADO">Censado</option>
                        <option value="NO LOCALIZADO">No Localizado</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Alerta de Movimiento</label>
                    <select name="alerta" class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm outline-none">
                        <option value="">Todas</option>
                        <option value="SIN VARIACIÓN">Sin Variación</option>
                        <option value="REUBICADO">Reubicado</option>
                        <option value="ALTA NUEVA">Alta Nueva</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: COLUMNAS A INCLUIR -->
        <div>
            <h3 class="text-white font-semibold text-base mb-3 border-b border-gray-700 pb-2">2. Columnas a Mostrar</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                @php
                    $columnasDisponibles = [
                        'codigo_patrimonial' => 'Código Patrimonial',
                        'denominacion' => 'Denominación',
                        'marca' => 'Marca',
                        'modelo' => 'Modelo',
                        'serie' => 'Serie',
                        'color' => 'Color',
                        'tipo' => 'Tipo',
                        'dimensiones' => 'Dimensiones',
                        'ubicacion' => 'Ubicación',
                        'estado' => 'Estado Físico',
                        'constatado_actual' => 'Constatado',
                        'alerta_movimiento' => 'Alerta'
                    ];
                @endphp
                @foreach($columnasDisponibles as $key => $label)
                    <label class="flex items-center space-x-2 bg-[#12141c] p-2.5 rounded-lg border border-gray-800 cursor-pointer hover:border-gray-700">
                        <input type="checkbox" name="columnas[]" value="{{ $key }}" {{ in_array($key, ['codigo_patrimonial', 'denominacion', 'marca', 'serie', 'ubicacion', 'estado']) ? 'checked' : '' }} class="rounded bg-gray-900 border-gray-700 text-blue-600 focus:ring-0">
                        <span class="text-gray-300">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- SECCIÓN 3: FORMATO DE SALIDA -->
        <div>
            <h3 class="text-white font-semibold text-base mb-3 border-b border-gray-700 pb-2">3. Formato de Salida</h3>
            <div class="flex gap-4">
                <label class="flex items-center space-x-2 bg-[#12141c] px-4 py-3 rounded-lg border border-gray-800 cursor-pointer flex-1">
                    <input type="radio" name="formato" value="pantalla" checked class="text-blue-600">
                    <div>
                        <span class="block text-white font-semibold text-sm">Vista de Impresión / PDF</span>
                        <span class="block text-gray-400 text-xs">Optimizado para imprimir directamente o guardar como PDF.</span>
                    </div>
                </label>
                <label class="flex items-center space-x-2 bg-[#12141c] px-4 py-3 rounded-lg border border-gray-800 cursor-pointer flex-1">
                    <input type="radio" name="formato" value="excel" class="text-blue-600">
                    <div>
                        <span class="block text-white font-semibold text-sm">Exportar a Excel (CSV)</span>
                        <span class="block text-gray-400 text-xs">Descargar archivo plano compatible con Microsoft Excel.</span>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-800">
            <button type="submit" class="bg-purple-600 hover:bg-purple-500 text-white font-bold px-6 py-2.5 rounded-xl transition shadow-lg flex items-center gap-2">
                🚀 Generar Reporte
            </button>
        </div>
    </form>
</div>
@endsection
