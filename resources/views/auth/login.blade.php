<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso | Sistema de Auditoría Patrimonial</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white shadow-xl rounded-lg flex max-w-4xl w-full overflow-hidden">
        
        <!-- Panel Izquierdo: Formulario -->
        <div class="w-full md:w-1/2 p-8 sm:p-12">
            <div class="mb-8">
                <h2 class="text-3xl font-extrabold text-gray-900">Bienvenido</h2>
                <p class="text-sm text-gray-500 mt-2">Ingresa tus credenciales para acceder al panel de auditoría y limpieza de datos.</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 text-red-700 p-4 rounded-md mb-6 border border-red-200">
                    <ul class="text-sm list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-5">
                    <label for="usu_dni" class="block text-sm font-medium text-gray-700 mb-1">Documento de Identidad (DNI)</label>
                    <input type="text" name="usu_dni" id="usu_dni" value="{{ old('usu_dni') }}" required autofocus
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition-colors"
                           placeholder="Ingresa tu DNI">
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                    <input type="password" name="password" id="password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 transition-colors"
                           placeholder="••••••••">
                </div>

                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-colors duration-200">
                    Ingresar al Sistema
                </button>
            </form>
        </div>

        <!-- Panel Derecho: Gráfico / Branding (Se oculta en móviles) -->
        <div class="hidden md:block w-1/2 bg-blue-700 px-12 py-16 flex flex-col justify-center relative overflow-hidden">
            <div class="relative z-10 text-white">
                <h3 class="text-2xl font-bold mb-4">Control Patrimonial ITEL</h3>
                <p class="text-blue-100 text-sm leading-relaxed mb-6">
                    Plataforma para el control de trazabilidad, conciliación y auditoría anuales de activos mediante arquitectura ETL automatizada.
                </p>
                <div class="flex items-center space-x-2 text-blue-200 text-sm font-semibold">
                    <span>Módulo de Limpieza Activo</span>
                </div>
            </div>
            <!-- Círculos decorativos de fondo -->
            <div class="absolute -bottom-16 -right-16 w-64 h-64 border-4 border-blue-500 rounded-full opacity-50"></div>
            <div class="absolute -top-12 -left-12 w-40 h-40 bg-blue-600 rounded-full opacity-50"></div>
        </div>

    </div>

</body>
</html>