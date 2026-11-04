@extends('admin.layout')

@section('content')
<div class="max-w-[96%] xl:max-w-7xl mx-auto pb-10 px-2 sm:px-4 text-gray-100">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white tracking-tight">Gestión de Catálogos</h1>
        <p class="text-gray-400 text-sm mt-1">Administra y edita las opciones disponibles para los formularios del sistema.</p>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-xl text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 bg-red-500/10 border border-red-500/20 text-red-400 p-4 rounded-xl text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Grid de Tarjetas de Catálogos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Tarjeta: Denominaciones -->
        <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-5 shadow-sm flex flex-col h-96">
            <h3 class="text-white font-bold mb-4 text-lg border-b border-gray-700 pb-2">📋 Denominaciones</h3>
            <div class="overflow-y-auto flex-1 pr-2 space-y-2">
                @foreach($denominaciones as $item)
                    <div class="bg-[#12141c] h-[46px] min-h-[46px] px-3 rounded-lg text-sm text-gray-300 border border-gray-800 flex justify-between items-center group hover:border-gray-700 transition overflow-hidden">
                        <span class="truncate pr-2 w-3/4" title="{{ $item->nombre }}">{{ $item->nombre }}</span>
                        <button type="button" onclick="abrirModalEdicion('denominacion', '', '{{ addslashes($item->nombre) }}')" 
                            class="bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 px-2.5 py-1 rounded-md text-xs font-semibold transition flex items-center gap-1 shadow-sm shrink-0">
                            ✏️ Editar
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tarjeta: Marcas -->
        <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-5 shadow-sm flex flex-col h-96">
            <h3 class="text-white font-bold mb-4 text-lg border-b border-gray-700 pb-2">🏷️ Marcas</h3>
            <form action="{{ route('admin.catalogos.store') }}" method="POST" class="flex gap-2 mb-4">
                @csrf
                <input type="hidden" name="tipo_catalogo" value="marca">
                <input type="text" name="nombre" placeholder="Nueva marca..." required class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2 w-full text-sm outline-none focus:ring-1 focus:ring-emerald-500">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg text-sm font-bold transition">Añadir</button>
            </form>
            <div class="overflow-y-auto flex-1 pr-2 space-y-2">
                @foreach($marcas as $item)
                    <div class="bg-[#12141c] h-[46px] min-h-[46px] px-3 rounded-lg text-sm text-gray-300 border border-gray-800 flex justify-between items-center group hover:border-gray-700 transition overflow-hidden">
                        <span class="truncate pr-2 w-3/4" title="{{ $item->mar_nombre }}">{{ $item->mar_nombre }}</span>
                        <button type="button" onclick="abrirModalEdicion('marca', '{{ $item->mar_id }}', '{{ addslashes($item->mar_nombre) }}')" 
                            class="bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 px-2.5 py-1 rounded-md text-xs font-semibold transition flex items-center gap-1 shadow-sm shrink-0">
                            ✏️ Editar
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tarjeta: Colores -->
        <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-5 shadow-sm flex flex-col h-96">
            <h3 class="text-white font-bold mb-4 text-lg border-b border-gray-700 pb-2">🎨 Colores</h3>
            <form action="{{ route('admin.catalogos.store') }}" method="POST" class="flex gap-2 mb-4">
                @csrf
                <input type="hidden" name="tipo_catalogo" value="color">
                <input type="text" name="nombre" placeholder="Nuevo color..." required class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2 w-full text-sm outline-none focus:ring-1 focus:ring-emerald-500">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg text-sm font-bold transition">Añadir</button>
            </form>
            <div class="overflow-y-auto flex-1 pr-2 space-y-2">
                @foreach($colores as $item)
                    <div class="bg-[#12141c] h-[46px] min-h-[46px] px-3 rounded-lg text-sm text-gray-300 border border-gray-800 flex justify-between items-center group hover:border-gray-700 transition overflow-hidden">
                        <span class="truncate pr-2 w-3/4" title="{{ $item->col_nombre }}">{{ $item->col_nombre }}</span>
                        <button type="button" onclick="abrirModalEdicion('color', '{{ $item->col_id }}', '{{ addslashes($item->col_nombre) }}')" 
                            class="bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 px-2.5 py-1 rounded-md text-xs font-semibold transition flex items-center gap-1 shadow-sm shrink-0">
                            ✏️ Editar
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tarjeta: Tipos de Activo -->
        <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-5 shadow-sm flex flex-col h-96">
            <h3 class="text-white font-bold mb-4 text-lg border-b border-gray-700 pb-2">📦 Tipos / Clasificador</h3>
            <form action="{{ route('admin.catalogos.store') }}" method="POST" class="flex gap-2 mb-4">
                @csrf
                <input type="hidden" name="tipo_catalogo" value="tipo">
                <input type="text" name="nombre" placeholder="Nuevo tipo..." required class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2 w-full text-sm outline-none focus:ring-1 focus:ring-emerald-500">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg text-sm font-bold transition">Añadir</button>
            </form>
            <div class="overflow-y-auto flex-1 pr-2 space-y-2">
                @foreach($clasificadores as $item)
                    <div class="bg-[#12141c] h-[46px] min-h-[46px] px-3 rounded-lg text-sm text-gray-300 border border-gray-800 flex justify-between items-center group hover:border-gray-700 transition overflow-hidden">
                        <span class="truncate pr-2 w-3/4" title="{{ $item->cla_descripcion }}">{{ $item->cla_descripcion }}</span>
                        <button type="button" onclick="abrirModalEdicion('tipo', '{{ $item->cla_id }}', '{{ addslashes($item->cla_descripcion) }}')" 
                            class="bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 px-2.5 py-1 rounded-md text-xs font-semibold transition flex items-center gap-1 shadow-sm shrink-0">
                            ✏️ Editar
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tarjeta: Ubicaciones -->
        <div class="bg-[#1e2330] border border-gray-800 rounded-xl p-5 shadow-sm flex flex-col h-96">
            <h3 class="text-white font-bold mb-4 text-lg border-b border-gray-700 pb-2">📍 Ubicaciones</h3>
            <form action="{{ route('admin.catalogos.store') }}" method="POST" class="flex gap-2 mb-4">
                @csrf
                <input type="hidden" name="tipo_catalogo" value="ubicacion">
                <input type="text" name="nombre" placeholder="Nueva ubicación..." required class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2 w-full text-sm outline-none focus:ring-1 focus:ring-emerald-500">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-3 py-2 rounded-lg text-sm font-bold transition">Añadir</button>
            </form>
            <div class="overflow-y-auto flex-1 pr-2 space-y-2">
                @foreach($ubicaciones as $item)
                    <div class="bg-[#12141c] h-[46px] min-h-[46px] px-3 rounded-lg text-sm text-gray-300 border border-gray-800 flex justify-between items-center group hover:border-gray-700 transition overflow-hidden">
                        <span class="truncate pr-2 w-3/4" title="{{ $item->ubi_nombre }}">{{ $item->ubi_nombre }}</span>
                        <button type="button" onclick="abrirModalEdicion('ubicacion', '{{ $item->ubi_id }}', '{{ addslashes($item->ubi_nombre) }}')" 
                            class="bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 px-2.5 py-1 rounded-md text-xs font-semibold transition flex items-center gap-1 shadow-sm shrink-0">
                            ✏️ Editar
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

<!-- ================= MODAL EDICIÓN DE CATÁLOGO ================= -->
<div id="modalEditarCatalogo" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-[#161922] border border-gray-800 rounded-2xl w-full max-w-md shadow-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center bg-[#1e2330]">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">✏️ Editar Elemento</h3>
            <button type="button" onclick="cerrarModalEdicion()" class="text-gray-400 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('admin.catalogos.update') }}" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            
            <input type="hidden" name="tipo_catalogo" id="edit_tipo">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="viejo_nombre" id="edit_viejo_nombre">

            <div>
                <label class="block text-xs font-semibold text-gray-400 mb-1 uppercase">Nuevo Nombre / Descripción</label>
                <input type="text" name="nombre" id="edit_nombre" required class="bg-[#12141c] border border-gray-700 text-white rounded-lg p-2.5 w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-800">
                <button type="button" onclick="cerrarModalEdicion()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm font-medium transition">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-sm font-medium transition shadow-md">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalEdicion(tipo, id, nombre) {
        document.getElementById('edit_tipo').value = tipo;
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_viejo_nombre').value = nombre; // Importante para denominaciones
        document.getElementById('modalEditarCatalogo').classList.remove('hidden');
    }

    function cerrarModalEdicion() {
        document.getElementById('modalEditarCatalogo').classList.add('hidden');
    }
</script>
@endsection