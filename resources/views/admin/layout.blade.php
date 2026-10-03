<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Panel Administrativo') | Auditoría Patrimonial</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Lógica CSS para el Modo Claro -->
    <style>
        body.light-mode { background-color: #f3f4f6 !important; color: #1f2937 !important; }
        
        body.light-mode aside, 
        body.light-mode .bg-\[\#161922\], 
        body.light-mode .bg-\[\#1e2330\],
        body.light-mode #modalEditar > div { 
            background-color: #ffffff !important; 
            border-color: #e5e7eb !important; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body.light-mode h1, 
        body.light-mode h2, 
        body.light-mode h3, 
        body.light-mode .text-white { color: #111827 !important; }
        
        body.light-mode .text-gray-400, 
        body.light-mode .text-gray-300 { color: #4b5563 !important; }
        
        body.light-mode input, 
        body.light-mode select { 
            background-color: #f9fafb !important; 
            color: #111827 !important; 
            border-color: #d1d5db !important; 
        }

        body.light-mode .divide-gray-800 > :not([hidden]) ~ :not([hidden]) {
            border-color: #e5e7eb !important;
        }
    </style>
</head>
<body class="bg-[#0f1117] text-gray-100 font-sans antialiased flex h-screen overflow-hidden transition-colors duration-300">

    <!-- BOTONERA FLOTANTE TEMA -->
    <div class="fixed top-5 right-6 z-50 flex items-center gap-3">
        <button onclick="toggleTheme()" class="bg-[#1e2330] hover:bg-blue-600 text-gray-300 hover:text-white border border-gray-700 p-2.5 rounded-full shadow-lg transition flex items-center justify-center" title="Alternar Modo Oscuro / Claro">
            <span id="theme-icon" class="text-lg leading-none">☀️</span>
        </button>
    </div>

    <!-- BARRA LATERAL -->
    <aside class="w-64 bg-[#161922] border-r border-gray-800 flex flex-col justify-between h-screen shrink-0">
        <div>
            <div class="p-6 border-b border-gray-800">
                <h2 class="text-base font-black text-white tracking-wider">Inventariado ITEL</h2>
                <p class="text-xs text-gray-400 mt-0.5">Control de Auditoría</p>
            </div>

            <!-- Menú -->
            <nav class="p-4 space-y-2 mt-4">
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:bg-[#1e2330] hover:text-white' }}">
                    📊 Vista Ejecutiva
                </a>
                <a href="{{ route('admin.usuarios.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition {{ request()->routeIs('admin.usuarios.*') ? 'bg-blue-600 text-white shadow-lg' : 'text-gray-400 hover:bg-[#1e2330] hover:text-white' }}">
                    👥 Gestión de Usuarios
                </a>
            </nav>
        </div>

        <!-- Pie de barra lateral (Usuario + Botón Cerrar Sesión Mejorado) -->
        <div class="p-4 border-t border-gray-800 bg-[#12141c]/50">
            <div class="flex items-center justify-between gap-2">
                <div class="truncate pr-1">
                    <p class="text-xs font-bold text-white truncate">{{ Auth::user()->usu_nombres ?? 'Administrador' }}</p>
                    <p class="text-[10px] text-gray-400 truncate">Administrador</p>
                </div>
                <!-- Botón Cerrar Sesión Estilizado -->
                <form action="{{ route('logout') }}" method="POST" class="m-0 shrink-0">
                    @csrf
                    <button type="submit" class="group relative bg-gray-800 hover:bg-red-600 text-gray-300 hover:text-white border border-gray-700 hover:border-red-500 p-2.5 rounded-xl transition-all duration-200 flex items-center justify-center shadow-md" title="Cerrar Sesión">
                        <span class="text-sm">🚪</span>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- CONTENIDO DINÁMICO -->
    <main class="flex-1 overflow-y-auto p-6 lg:p-10 pt-20"> 
        @if(session('success'))
            <div class="bg-emerald-950/60 border border-emerald-500/50 text-emerald-300 p-4 rounded-xl text-sm font-medium mb-6 shadow-lg">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-950/60 border border-red-500/50 text-red-300 p-4 rounded-xl text-sm font-medium mb-6 shadow-lg">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        function toggleTheme() {
            const body = document.body;
            body.classList.toggle('light-mode');
            const isLight = body.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            document.getElementById('theme-icon').innerText = isLight ? '🌙' : '☀️';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.body.classList.add('light-mode');
                document.getElementById('theme-icon').innerText = '🌙';
            }
        });
    </script>
</body>
</html>