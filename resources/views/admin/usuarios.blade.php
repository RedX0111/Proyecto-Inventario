@extends('admin.layout')

@section('title', 'Gestión de Usuarios')

@section('content')
    <div class="max-w-6xl mx-auto space-y-8 relative">
        
        <div class="bg-[#161922] p-6 rounded-2xl border border-gray-800 shadow-xl">
            <span class="text-xs font-semibold uppercase tracking-wider text-blue-400 bg-blue-950 px-3 py-1 rounded-full border border-blue-800">Módulo de Seguridad</span>
            <h1 class="text-2xl font-black text-white mt-2">Gestión de Usuarios</h1>
        </div>

        <!-- Formulario -->
        <div class="bg-[#161922] p-6 rounded-2xl border border-gray-800 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white">Registrar Nuevo Usuario</h3>
            
            <form action="{{ route('admin.usuarios.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs text-gray-400 uppercase mb-1">DNI</label>
                    <input type="text" name="usu_dni" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 uppercase mb-1">Nombres</label>
                    <input type="text" name="usu_nombres" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 uppercase mb-1">Apellidos</label>
                    <input type="text" name="usu_apellidos" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 uppercase mb-1">Correo Electrónico</label>
                    <input type="email" name="usu_correo" class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 uppercase mb-1">Contraseña</label>
                    <input type="password" name="password" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 uppercase mb-1">Rol</label>
                    <select name="rol_id" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        <option value="">Seleccione un rol...</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->rol_id }}">
                                {{ $rol->rol_nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3 flex justify-end mt-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold uppercase tracking-wider px-6 py-3 rounded-xl transition">Crear Usuario</button>
                </div>
            </form>
        </div>

        <!-- Tabla -->
        <div class="bg-[#161922] p-6 rounded-2xl border border-gray-800 shadow-xl space-y-4">
            <h3 class="text-base font-bold text-white">Directorio</h3>
            <div class="overflow-x-auto rounded-xl border border-gray-800">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-[#1e2330] text-xs text-gray-400 uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4">DNI</th>
                            <th class="py-3 px-4">Nombre Completo</th>
                            <th class="py-3 px-4">Rol</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800 text-gray-300">
                        <?php foreach($usuarios as$usr): ?>
                        <tr class="hover:bg-[#1e2330]/40 transition">
                            <td class="py-3 px-4 font-mono text-blue-400"><?php echo $usr->usu_dni; ?></td>
                            <td class="py-3 px-4"><?php echo $usr->usu_nombres . ' ' .$usr->usu_apellidos; ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-blue-950 text-blue-300 rounded-lg text-xs font-bold border border-blue-800/50"><?php echo $usr->rol_nombre; ?></span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <?php if($usr->usu_activo == 1): ?>
                                    <span class="px-2 py-1 bg-emerald-950 text-emerald-400 rounded-md text-xs font-bold border border-emerald-800/50">ACTIVO</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 bg-rose-950 text-rose-400 rounded-md text-xs font-bold border border-rose-800/50">INACTIVO</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    <!-- Botón de Editar configurado -->
                                    <button type="button" 
                                            onclick="abrirModalEditar(this)"
                                            data-id="{{ $usr->usu_id }}"
                                            data-dni="{{ $usr->usu_dni }}"
                                            data-nombres="{{ $usr->usu_nombres }}"
                                            data-apellidos="{{ $usr->usu_apellidos }}"
                                            data-correo="{{ $usr->usu_correo }}"
                                            data-rol="{{ $usr->rol_id }}"
                                            data-activo="{{ $usr->usu_activo }}"
                                            class="bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition" title="Editar">
                                        ✏️ Editar
                                    </button>

                                    <!-- Lógica para ocultar el botón si es el usuario actual -->
                                    @if(auth()->id() != $usr->usu_id)
                                        <!-- Formulario para Cambiar Estado -->
                                        <form action="{{ route('admin.usuarios.destroy', $usr->usu_id) }}" method="POST" class="m-0">
                                            @csrf
                                            @method('DELETE') 
                                            
                                            @if($usr->usu_activo == 1)
                                                <!-- Botón de Desactivar (Rojo) -->
                                                <button type="submit" 
                                                        class="bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white border border-red-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                                                        onclick="return confirm('¿Estás seguro de desactivar a este usuario? Perderá el acceso al sistema.')" title="Desactivar acceso">
                                                    Desactivar
                                                </button>
                                            @else
                                                <!-- Botón de Activar (Verde Esmeralda) -->
                                                <button type="submit" 
                                                        class="bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500 hover:text-white border border-emerald-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                                                        onclick="return confirm('¿Estás seguro de reactivar a este usuario? Recuperará su acceso al sistema.')" title="Reactivar acceso">
                                                    Activar
                                                </button>
                                            @endif
                                        </form>
                                    @endif
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4"><?php echo $usuarios->links(); ?></div>
        </div>
    </div>

    <!-- Modal Editar (Con campos extendidos) -->
    <div id="modalEditar" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center p-4 z-50">
        <div class="bg-[#161922] w-full max-w-2xl rounded-2xl border border-gray-800 p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-5 border-b border-gray-800 pb-3">
                <h3 class="text-lg font-bold text-white uppercase tracking-wider">Editar Usuario</h3>
                <button type="button" onclick="cerrarModalEditar()" class="text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            <form id="formEditar" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1 font-semibold">DNI</label>
                            <input type="text" id="edit_dni" name="usu_dni" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1 font-semibold">Correo Electrónico</label>
                            <input type="email" id="edit_correo" name="usu_correo" class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1 font-semibold">Nombres</label>
                            <input type="text" id="edit_nombres" name="usu_nombres" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1 font-semibold">Apellidos</label>
                            <input type="text" id="edit_apellidos" name="usu_apellidos" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 uppercase mb-1 font-semibold">Rol Asignado</label>
                            <select id="edit_rol" name="rol_id" required class="w-full bg-[#1e2330] border border-gray-700 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500">
                                <?php foreach($roles as$rol): ?>
                                    <option value="<?php echo $rol->rol_id; ?>"><?php echo $rol->rol_nombre; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-blue-400 uppercase mb-1 font-semibold">Nueva Contraseña <span class="text-gray-500 normal-case">(Opcional)</span></label>
                            <input type="password" id="edit_password" name="password" placeholder="Dejar en blanco para no cambiar" class="w-full bg-[#1e2330] border border-blue-900/50 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-blue-500 placeholder-gray-600">
                        </div>
                    </div>

                    <div id="contenedor_estado" class="flex items-center gap-3 mt-4 p-4 bg-[#1e2330] rounded-xl border border-gray-700">
                        <input type="checkbox" id="edit_activo" name="usu_activo" value="1" class="w-5 h-5 text-blue-600 bg-gray-900 border-gray-600 rounded">
                        <label class="text-sm font-bold text-gray-300">Mantener este usuario activo en el sistema</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-800">
                        <button type="button" onclick="cerrarModalEditar()" class="bg-gray-800 hover:bg-gray-700 text-gray-300 text-xs font-bold uppercase tracking-wider px-5 py-2.5 rounded-xl transition">Cancelar</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold uppercase tracking-wider px-6 py-2.5 rounded-xl transition">Guardar Cambios</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalEditar(btn) {
            const id = btn.dataset.id;
            document.getElementById('formEditar').action = `/dashboard/usuarios/${id}`;
            document.getElementById('edit_dni').value = btn.dataset.dni;
            document.getElementById('edit_nombres').value = btn.dataset.nombres;
            document.getElementById('edit_apellidos').value = btn.dataset.apellidos;
            document.getElementById('edit_correo').value = btn.dataset.correo || '';
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_rol').value = btn.dataset.rol;
            document.getElementById('edit_activo').checked = (btn.dataset.activo == "1");

            if (id == "{{ auth()->id() }}") {
                document.getElementById('contenedor_estado').style.display = 'none';
            } else {
                document.getElementById('contenedor_estado').style.display = 'flex';
            }

            document.getElementById('modalEditar').classList.remove('hidden');
        }
        function cerrarModalEditar() {
            document.getElementById('modalEditar').classList.add('hidden');
        }
    </script>
@endsection