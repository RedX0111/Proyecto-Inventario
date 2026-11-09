@extends('admin.layout')

@section('content')
<div class="max-w-[96%] xl:max-w-7xl mx-auto pb-10 px-2 sm:px-4 text-gray-100">
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Validación y Sincronización de Data con Patrimonio</h1>
            <p class="text-gray-400 text-sm mt-1">Sube el archivo CSV oficial para contrastar, validar y actualizar los atributos del padrón.</p>
        </div>
    </div>

    <!-- SECCIÓN DE INSTRUCCIONES Y GUÍA RÁPIDA -->
    <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-6 shadow-sm mb-6">
        <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
            📖 Guía de Estructura para el Archivo CSV
        </h3>
        <p class="text-gray-400 text-sm mb-4">El sistema actualizará automáticamente marcas, modelos, series, colores, ubicaciones y estados de los códigos coincidentes (respetando la denominación actual). Asegúrate de que las columnas del archivo sean exactamente estas:</p>
        
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 mb-4">
            @foreach($columnasRequeridasInfo as $req)
                <div class="bg-[#12141c] border border-gray-800 p-3 rounded-lg flex flex-col justify-between">
                    <div>
                        <span class="font-bold text-blue-400 font-mono text-xs block truncate" title="{{ $req['columna'] }}">{{ $req['columna'] }}</span>
                        <span class="text-[9px] px-1.5 py-0.5 rounded font-semibold inline-block mt-1 {{ $req['obligatorio'] == 'Sí' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                            {{ $req['obligatorio'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-[#12141c] border border-gray-800 p-3 rounded-xs text-xs text-gray-300 font-mono">
            <span class="text-gray-400 font-bold">Cabecera de ejemplo:</span> 
            codigo_patrimonial,denominacion,marca,modelo,serie,color,ubicacion,estado
        </div>
    </div>

    <!-- SECCIÓN DE SUBIDA DE ARCHIVO -->
    <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-6 shadow-sm">
        <h3 class="text-lg font-bold text-white mb-2">Seleccionar Archivo CSV</h3>
        <p class="text-gray-400 text-sm mb-6">Sube tu documento delimitado por comas para ejecutar el análisis y actualización masiva.</p>

        <form action="{{ route('admin.validarPatrimonio.procesar') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="border-2 border-dashed border-gray-700 rounded-xl p-8 text-center bg-[#12141c]">
                <input type="file" name="archivo_csv" accept=".csv" required class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                <p class="text-xs text-gray-500 mt-3">Soporta únicamente archivos .csv con codificación UTF-8</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-2.5 rounded-lg text-sm font-bold shadow-lg transition">
                    ⚡ Validar y Actualizar Padrón
                </button>
            </div>
        </form>
    </div>

    <!-- RESULTADOS DEL PROCESAMIENTO -->
    @if(isset($resultadosValidacion))
    <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-6 shadow-sm mt-6">
        <h3 class="text-md font-bold text-white mb-3">Resultados de la Sincronización</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-[#12141c] p-4 rounded-lg border border-gray-800">
                <p class="text-xs text-gray-400">Total de filas leídas en el CSV:</p>
                <p class="text-2xl font-bold text-white mt-1">{{ $resultadosValidacion['total'] }}</p>
            </div>
            <div class="bg-[#12141c] p-4 rounded-lg border border-gray-800">
                <p class="text-xs text-gray-400">Activos actualizados correctamente:</p>
                <p class="text-2xl font-bold text-emerald-400 mt-1">{{ $resultadosValidacion['actualizados'] }}</p>
            </div>
        </div>
        
        @if(count($resultadosValidacion['errores']) > 0)
            <div class="bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl text-sm mt-4 space-y-1">
                <p class="font-bold">Incidencias encontradas durante el proceso:</p>
                @foreach($resultadosValidacion['errores'] as $error)
                    <p>• {{ $error }}</p>
                @endforeach
            </div>
        @else
            <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-xl text-sm mt-4 font-semibold">
                🎉 ¡Proceso completado con éxito! Todos los datos fueron validados y la información fue actualizada de forma limpia en el sistema.
            </div>
        @endif
    </div>
    @endif
</div>
@endsection