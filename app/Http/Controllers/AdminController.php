<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    /**
     * Muestra el Dashboard Administrativo con KPIs, gráficos, filtros y tabla de padrón.
     */
    public function index(Request $request)
    {
        // 1. Obtener los períodos fiscales directamente de tu tabla bd_periodos
        $periodos = DB::table('bd_periodos_fiscales')->orderBy('per_anio', 'desc')->get();
    
        // Período seleccionado por defecto
        $periodoSeleccionado = $request->input('periodo');
        if (!$periodoSeleccionado) {
            $periodoActivo = $periodos->where('per_cerrado', 0)->first();
            $periodoSeleccionado = $periodoActivo ? $periodoActivo->per_id : ($periodos->first()->per_id ?? 1);
        }

        // Obtener el año correspondiente al período seleccionado (ej: '2026' o '2025')
        $periodoActualObj = $periodos->where('per_id', $periodoSeleccionado)->first();
        $anioSeleccionado = $periodoActualObj ? $periodoActualObj->per_anio : date('Y');

        // 2. Obtener ubicaciones para los filtros y modales
        try {
            $ubicaciones = DB::table('bd_ubicaciones')->orderBy('ubi_nombre')->get();
            $colores = DB::table('col_colores')->orderBy('col_nombre')->get();
            $modelos = DB::table('mod_modelos')->orderBy('mod_nombre')->get();
            $clasificadores = DB::table('sla_clasificadores')->orderBy('cla_descripcion')->get();
            
            // NUEVOS: Marcas y Estados Físicos
            $marcas = DB::table('mar_marcas')->orderBy('mar_nombre')->get();
            $estados = DB::table('bd_estados_fisicos')->orderBy('est_nombre')->get();
        } catch (\Exception $e) {
            $ubicaciones = $colores = $modelos = $clasificadores = $marcas = $estados = collect([]);
        }

        // 3. Consulta base para el Padrón Maestro filtrada por el año de auditoría
        $query = DB::table('v_bd_tabla_master')->where('anio_auditoria', $anioSeleccionado);

        // Filtro de búsqueda (Código o Denominación)
        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where(function($q) use ($busqueda) {
                $q->where('codigo_patrimonial', 'like', "%{$busqueda}%")
                  ->orWhere('denominacion', 'like', "%{$busqueda}%");
            });
        }

        // Filtro por Ubicación
        if ($request->filled('ubicacion')) {
            $query->where('ubicacion', $request->input('ubicacion'));
        }

        // Filtro por Alerta de Movimiento
        if ($request->filled('alerta')) {
            $alertaVal = $request->input('alerta');
            $query->where(DB::raw("alerta_movimiento COLLATE utf8mb4_general_ci"), '=', DB::raw("'$alertaVal' COLLATE utf8mb4_general_ci"));
        }

        // 1. Identificar el año exacto del período seleccionado
        $periodoActual = DB::table('bd_periodos_fiscales')->where('per_id', $periodoSeleccionado)->first();
        $anioFiltro = $periodoActual ? $periodoActual->per_anio : date('Y');

        // 2. Construir la consulta base como una "Máquina del Tiempo"
        // Iniciamos DESDE la tabla de censo histórico/staging, filtrando estrictamente por ese año.
        // Asume que tu tabla de movimientos/censo se llama 'bd_staging_censo' y la maestra 'bd_activos'
       // Consulta base limpia usando únicamente el staging/censo para verificar que los datos fluyan
        $query = DB::table('bd_staging_censo as censo')
            ->select(
                'censo.codigo_patrimonial',
                'censo.denominacion',
                'censo.marca',
                'censo.modelo',
                'censo.tipo',
                'censo.color',
                'censo.dimensiones',
                'censo.ubicacion',
                'censo.estado',
                'censo.constatado_actual',
                'censo.alerta_movimiento',
                'censo.anio_auditoria'
            );

        // Filtro de periodo opcional
        if ($request->filled('periodo')) {
            $periodoActual = DB::table('bd_periodos_fiscales')->where('per_id', $request->periodo)->first();
            if ($periodoActual) {
                $query->where('censo.anio_auditoria', $periodoActual->per_anio);
            }
        }

        // Filtros de búsqueda tradicionales
        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where(function($q) use ($busqueda) {
                $q->where('censo.codigo_patrimonial', 'like', "%{$busqueda}%")
                  ->orWhere('censo.denominacion', 'like', "%{$busqueda}%");
            });
        }

        if ($request->filled('ubicacion')) {
            $query->where('censo.ubicacion', $request->ubicacion);
        }

        if ($request->filled('estado')) {
            $query->where('censo.estado', $request->estado);
        }

        if ($request->filled('alerta')) {
            $query->where('censo.alerta_movimiento', $request->alerta);
        }

        // Paginación final
        $registrosVista = $query->orderBy('censo.codigo_patrimonial', 'asc')->paginate(15);

        // 4. Indicadores Clave (KPIs) filtrados por el año seleccionado
        $totalActivos = DB::table('v_bd_tabla_master')->where('anio_auditoria', $anioSeleccionado)->count();
        
        $verificados = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->where(DB::raw("constatado_actual COLLATE utf8mb4_general_ci"), '=', DB::raw("'CENSADO' COLLATE utf8mb4_general_ci"))
            ->count();
            
        try {
            $pendientes = DB::table('bd_staging_censo')->count();
        } catch (\Exception $e) {
            $pendientes = 0;
        }

        $alertasMovimiento = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->where(DB::raw("alerta_movimiento COLLATE utf8mb4_general_ci"), '=', DB::raw("'REUBICADO' COLLATE utf8mb4_general_ci"))
            ->count();
        
        $porcentajeAvance = $totalActivos > 0 ? round(($verificados / $totalActivos) * 100, 1) : 0;

        // 5. Datos para los Gráficos Estadísticos (Chart.js)
        $topUbicaciones = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->select('ubicacion', 
                DB::raw("SUM(CASE WHEN constatado_actual COLLATE utf8mb4_general_ci = 'CENSADO' COLLATE utf8mb4_general_ci THEN 1 ELSE 0 END) as verificados"),
                DB::raw("SUM(CASE WHEN constatado_actual COLLATE utf8mb4_general_ci != 'CENSADO' COLLATE utf8mb4_general_ci THEN 1 ELSE 0 END) as pendientes")
            )
            ->groupBy('ubicacion')
            ->limit(10)
            ->get();

        $chartUbiLabels = $topUbicaciones->pluck('ubicacion');
        $chartUbiVerificados = $topUbicaciones->pluck('verificados');
        $chartUbiPendientes = $topUbicaciones->pluck('pendientes');

        $estadosFisicos = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();

        $chartEstLabels = $estadosFisicos->pluck('estado');
        $chartEstData = $estadosFisicos->pluck('total');

        return view('admin.dashboard', compact(
            'periodos',
            'periodoSeleccionado',
            'ubicaciones',
            'registrosVista',
            'totalActivos',
            'verificados',
            'pendientes',
            'alertasMovimiento',
            'porcentajeAvance',
            'chartUbiLabels',
            'chartUbiVerificados',
            'chartUbiPendientes',
            'chartEstLabels',
            'chartEstData',
            'colores',
            'modelos',
            'clasificadores',
            'marcas',
            'estados'
        ));
    }

    /**
     * Sincroniza los datos desde el Web App de Google Sheets hacia la tabla Staging.
     */
    public function jalarDataDeSheets()
    {
        $urlGoogleScript = env('GOOGLE_SHEETS_WEBAPP_URL');

        if (!$urlGoogleScript) {
            return back()->with('error', 'La URL de Google Sheets no está configurada en el archivo .env.');
        }

        DB::beginTransaction();
        try {
            $response = Http::withoutVerifying()->timeout(60)->get($urlGoogleScript);
            
            if (!$response->successful()) {
                throw new \Exception('No se pudo establecer conexión con Google Sheets.');
            }

            $items = $response->json();

            if (isset($items['status']) && $items['status'] === 'error') {
                throw new \Exception('Error en Apps Script: ' . $items['message']);
            }

            foreach ($items as $item) {
                DB::statement('CALL sp_procesar_item_censo_complejo(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                    $item['orden'] ?? null, 
                    $item['codigo'] ?? null, 
                    
                    // 1. DENOMINACIÓN
                    $item['denominacion'] ?? 'SIN DENOMINACION', 
                    
                    $item['marca'] ?? '', 
                    $item['modelo'] ?? '', 
                    $item['tipo'] ?? '', 
                    $item['color'] ?? '',
                    $item['serie'] ?? '', 
                    
                    // 2. DIMENSIONES
                    $item['dimensiones'] ?? 'SIN DIMENSIONES', 
                    
                    $item['fecha'] ?? now()->toDateString(),
                    
                    // 3. ESTADO
                    $item['estado'] ?? 'BUENO', 
                    
                    // 4. UBICACIÓN
                    $item['ubicacion'] ?? 'GENERAL', 
                    
                    $item['observaciones'] ?? '',
                    $item['dni_auditor'] ?? 'SISTEMA',
                    $item['anio_auditoria'] ?? date('Y'),
                    $item['constatado_actual'] ?? 'CENSADO',
                    $item['alerta_movimiento'] ?? 'SIN VARIACIÓN'
                ]);
            }

            DB::commit();
            return back()->with('success', '¡Sincronización desde Google Sheets completada con éxito!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error crítico al jalar los datos: ' . $e->getMessage());
        }
    }

    /**
     * Aprueba masivamente los registros pendientes de Staging hacia el Padrón Maestro.
     */
    public function aprobarLote()
    {
        try {
            // Ejecuta el procedimiento almacenado que consolida desde los lotes y censos hacia el maestro
            DB::statement('CALL sp_aprobar_lote_censo()');

            return back()->with('success', '¡Lote aprobado y consolidado en el padrón maestro exitosamente!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al aprobar el lote: ' . $e->getMessage());
        }
    }

    /**
     * Cierra el período fiscal actual.
     */
    public function cerrarPeriodo(Request $request, $id)
    {
        try {
            // 1. Buscamos el período en la base de datos
            $periodo = DB::table('bd_periodos_fiscales')->where('per_id', $id)->first();

            // 2. Verificamos que exista
            if (!$periodo) {
                return back()->with('error', 'El período no existe.');
            }

            // 3. CANDADO DE SEGURIDAD: Si ya está cerrado, abortamos la operación
            if ($periodo->per_cerrado == 1) {
                return back()->with('error', 'Acción denegada: Este período ya fue cerrado el ' . $periodo->per_fecha_cierre . ' y no puede modificarse.');
            }

            // 4. Si está abierto, procedemos a cerrarlo y estampar la fecha
            DB::table('bd_periodos_fiscales')
                ->where('per_id', $id)
                ->update([
                    'per_cerrado' => 1,
                    'per_fecha_cierre' => now()->toDateString()
                ]);

            return back()->with('success', '¡El período ha sido cerrado definitivamente!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cerrar el período: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza un activo patrimonial desde el modal de edición del dashboard.
     */
    public function activosUpdate(Request $request, $codigo)
    {
        $request->validate([
            'denominacion' => 'required|string|max:255',
            'estado' => 'required|string|max:50',
            'ubicacion' => 'required|string|max:50',
        ]);

        try {
            DB::table('bd_activos')
                ->where('act_codigo', $codigo)
                ->update([
                    'act_denominacion' => $request->input('denominacion'),
                    'act_marca' => $request->input('marca'),
                    'act_modelo' => $request->input('modelo'),
                    'act_tipo' => $request->input('tipo'),
                    'act_color' => $request->input('color'),
                    'act_dimensiones' => $request->input('dimensiones'),
                    'act_estado' => $request->input('estado'),
                    'act_ubicacion_codigo' => $request->input('ubicacion'),
                    'updated_at' => now(),
                ]);

            return back()->with('success', "¡Activo {$codigo} actualizado correctamente!");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar el activo: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el panel de gestión de usuarios.
     */
    public function usuariosIndex()
    {
        // 1. Obtener los usuarios cruzando con la nueva tabla de roles
        $usuarios = DB::table('bd_usuarios')
            ->join('bd_roles', 'bd_usuarios.rol_id', '=', 'bd_roles.rol_id')
            ->select('bd_usuarios.*', 'bd_roles.rol_nombre')
            ->get();

        // 2. Obtener los roles para el select del formulario (Crear/Editar)
        $roles = DB::table('bd_roles')->get();

        return view('admin.usuarios', compact('usuarios', 'roles'));
    }
}