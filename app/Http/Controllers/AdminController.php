<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        // 1. Obtener los períodos fiscales
        $periodos = DB::table('bd_periodos_fiscales')->orderBy('per_anio', 'desc')->get();
    
        // Período seleccionado
        $periodoSeleccionado = $request->input('periodo');
        if (!$periodoSeleccionado) {
            $periodoActivo = $periodos->where('per_cerrado', 0)->first();
            $periodoSeleccionado = $periodoActivo ? $periodoActivo->per_id : ($periodos->first()->per_id ?? 1);
        }

        $periodoActualObj = $periodos->where('per_id', $periodoSeleccionado)->first();
        $anioSeleccionado = $periodoActualObj ? $periodoActualObj->per_anio : date('Y');

        // 2. Obtener catálogos
        try {
            $ubicaciones = DB::table('bd_ubicaciones')->orderBy('ubi_nombre')->get();
            $colores = DB::table('col_colores')->orderBy('col_nombre')->get();
            $modelos = DB::table('mod_modelos')->orderBy('mod_nombre')->get();
            $clasificadores = DB::table('sla_clasificadores')->orderBy('cla_descripcion')->get();
            $marcas = DB::table('mar_marcas')->orderBy('mar_nombre')->get();
            $estados = DB::table('bd_estados_fisicos')->orderBy('est_nombre')->get();
        } catch (\Exception $e) {
            $ubicaciones = $colores = $modelos = $clasificadores = $marcas = $estados = collect([]);
        }

        // 3. Consulta base para la tabla principal
        $query = DB::table('v_bd_tabla_master')->where('anio_auditoria', $anioSeleccionado);

        if ($request->filled('buscar')) {
            $busqueda = $request->input('buscar');
            $query->where(function($q) use ($busqueda) {
                $q->where('codigo_patrimonial', 'like', "%{$busqueda}%")
                  ->orWhere('denominacion', 'like', "%{$busqueda}%");
            });
        }
        if ($request->filled('ubicacion')) $query->where('ubicacion', $request->input('ubicacion'));
        if ($request->filled('estado')) $query->where('estado', $request->input('estado'));
        if ($request->filled('alerta')) {
            $alertaVal = $request->input('alerta');
            $query->where('alerta_movimiento', 'ILIKE', $alertaVal);
        }

        $registrosVista = $query->orderBy('codigo_patrimonial', 'asc')->paginate(15);

        // 4. Indicadores Clave (KPIs)
        $totalActivos = DB::table('v_bd_tabla_master')->where('anio_auditoria', $anioSeleccionado)->count();
        
        $verificados = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->where('constatado_actual', 'ILIKE', 'CENSADO')
            ->count();
            
        $pendientes = $totalActivos - $verificados;
        $porcentajeAvance = $totalActivos > 0 ? round(($verificados / $totalActivos) * 100, 1) : 0;

        $alertasMovimiento = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->where(function($q) {
                $q->where('alerta_movimiento', 'ILIKE', '%REUBICADO%')
                ->orWhere('alerta_movimiento', 'ILIKE', '%ALERTA%');
            })
            ->count();

        // 5. Gráfico de Ubicaciones (Consulta optimizada)
        $ubicacionesStats = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->select(
                'ubicacion',
                DB::raw('COUNT(*) as total_activos'),
                // Contamos cuántos de esos activos ya están "CENSADOS"
                DB::raw("SUM(CASE WHEN constatado_actual ILIKE 'CENSADO' THEN 1 ELSE 0 END) as verificados")
            )
            ->groupBy('ubicacion')
            ->orderBy('total_activos', 'desc') 
            ->limit(10)
            ->get();

        $chartUbiLabels = [];
        $chartUbiVerificados = [];
        $chartUbiPendientes = [];

        foreach ($ubicacionesStats as $stat) {
            $chartUbiLabels[] = $stat->ubicacion;
            $chartUbiVerificados[] = (int) $stat->verificados;
            // El pendiente se calcula restando lo verificado al total de activos en esa ubicación
            $chartUbiPendientes[] = (int) $stat->total_activos - (int) $stat->verificados; 
        }

        // Gráfico de Estados Físicos
        $estadosFisicos = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get();

        $chartEstLabels = $estadosFisicos->pluck('estado');
        $chartEstData = $estadosFisicos->pluck('total');

        // Gráfico de Tipos (Nuevo)
        $tiposActivos = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->select('tipo', DB::raw('count(*) as total'))
            ->groupBy('tipo')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        $chartTipoLabels = $tiposActivos->pluck('tipo');
        $chartTipoData = $tiposActivos->pluck('total');

        // 6. NUEVO GRÁFICO: Distribución por Tipo de Activo
        $tiposActivos = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->whereNotNull('tipo') // Ignoramos nulos
            ->where('tipo', '!=', '') // Ignoramos vacíos
            ->select('tipo', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo')
            ->orderBy('total', 'desc') // Ordenamos del tipo con más activos al menor
            ->limit(7) // Traemos el Top 7 para no saturar el gráfico visualmente
            ->get();

        // Convertimos a arreglos simples para que Chart.js los procese sin errores
        $chartTipoLabels = $tiposActivos->pluck('tipo')->toArray();
        $chartTipoData = $tiposActivos->pluck('total')->toArray();

        // Retorno a la vista
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
            'chartTipoLabels',
            'chartTipoData',
            'chartTipoLabels',
            'chartTipoData',
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
                DB::table('bd_staging_censo')->insert([
                    'stg_orden' => $item['orden'] ?? null,
                    'codigo_patrimonial' => $item['codigo'] ?? null,
                    'denominacion' => $item['denominacion'] ?? 'SIN DENOMINACION',
                    'marca' => $item['marca'] ?? '',
                    'modelo' => $item['modelo'] ?? '',
                    'tipo' => $item['tipo'] ?? '',
                    'color' => $item['color'] ?? '',
                    'serie' => $item['serie'] ?? '',
                    'dimensiones' => $item['dimensiones'] ?? 'SIN DIMENSIONES',
                    'fecha_censo' => $item['fecha'] ?? now()->toDateString(),
                    'anio_auditoria' => $item['anio_auditoria'] ?? date('Y'),
                    'estado' => $item['estado'] ?? 'BUENO',
                    'constatado_actual' => $item['constatado_actual'] ?? 'CENSADO',
                    'alerta_movimiento' => $item['alerta_movimiento'] ?? 'SIN VARIACIÓN',
                    'ubicacion' => $item['ubicacion'] ?? 'GENERAL',
                    'observaciones' => $item['observaciones'] ?? '',
                    'dni_auditor' => $item['dni_auditor'] ?? 'SISTEMA',
                    'creado_en' => now(),
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
            DB::beginTransaction();

            // 1. Obtener todos los registros encolados en staging
            $registros = DB::table('bd_staging_censo')->get();

            if ($registros->isEmpty()) {
                return back()->with('error', 'No hay datos en el lote para aprobar.');
            }

            // 2. Procesar cada registro hacia el padrón maestro
            foreach ($registros as $reg) {
                // updateOrInsert busca por el primer arreglo (act_codigo). 
                // Si existe, lo actualiza con el segundo arreglo; si no, lo crea.
                DB::table('bd_activos_maestro')->updateOrInsert(
                    ['act_codigo' => $reg->codigo_patrimonial],
                    [
                        'act_denominacion' => $reg->denominacion,
                        'act_serie' => $reg->serie,
                        'act_dimensiones' => $reg->dimensiones,
                        'updated_at' => now(),
                        // Nota: Si tu SP antiguo resolvía los IDs de marca, color o modelo 
                        // a partir del texto, debes agregar esas consultas previas aquí.
                    ]
                );
            }

            // 3. Limpiar la tabla de staging una vez consolidados los datos
            DB::table('bd_staging_censo')->truncate();

            DB::commit();
            return back()->with('success', '¡Lote aprobado y consolidado en el padrón maestro exitosamente!');

        } catch (\Exception $e) {
            DB::rollBack();
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
            'dimensiones' => 'nullable|string|max:100',
        ]);

        try {
            // 1. Actualizamos utilizando tu código original que sí funciona
            DB::table('bd_activos_maestro')
                ->where('act_codigo', $codigo)
                ->update([
                    'act_denominacion' => $request->input('denominacion'),
                    'act_dimensiones' => $request->input('dimensiones', 'SIN DIMENSIONES'),
                    'mod_id' => $request->input('mod_id'),
                    'col_id' => $request->input('col_id'),
                    'cla_id' => $request->input('cla_id'),
                    'updated_at' => now(),
                ]);

            // 2. Buscamos el act_id para registrar la trazabilidad sin interferir con la actualización
            $actId = DB::table('bd_activos_maestro')->where('act_codigo', $codigo)->value('act_id');

            if ($actId) {
                DB::table('aud_bitacora_trazabilidad')->insert([
                    'act_id' => $actId,
                    'usu_id' => auth()->id() ?? 1,
                    'aud_accion' => 'ACTUALIZACION',
                    'aud_detalle' => "Actualización manual del activo {$codigo}: Denominación cambiada a '{$request->input('denominacion')}'",
                    'created_at' => now(),
                ]);
            }

            return back()->with('success', "¡Activo {$codigo} actualizado correctamente en el padrón maestro y registrado en la bitácora!");

        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar el activo: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el panel de gestión de usuarios.
     */
    public function usuariosIndex()
    {
        $usuarios = DB::table('bd_usuarios')
            ->join('bd_roles', 'bd_usuarios.rol_id', '=', 'bd_roles.rol_id')
            ->select('bd_usuarios.*', 'bd_roles.rol_nombre')
            ->paginate(10);

        $roles = DB::table('bd_roles')->get()->unique('rol_nombre');

        return view('admin.usuarios', compact('usuarios', 'roles'));
    }

    public function usuariosStore(Request $request)
    {
        try {
            // Insertamos capturando los campos adaptándose a cualquier nombre que tenga el input en tu Blade
            DB::table('bd_usuarios')->insert([
                'usu_dni' => $request->input('dni') ?? $request->input('usu_dni'),
                'usu_nombres' => $request->input('nombres') ?? $request->input('usu_nombres'),
                'usu_apellidos' => $request->input('apellidos') ?? $request->input('usu_apellidos'),
                'usu_correo' => $request->input('correo') ?? $request->input('usu_correo'),
                'password' => bcrypt($request->input('password') ?? '12345678'), 
                'rol_id' => $request->input('rol_id') ?? 2, 
                'usu_activo' => 1,
                'created_at' => now(),
            ]);

            return back()->with('success', '¡Usuario registrado correctamente!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al registrar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza un usuario existente en la base de datos.
     */
    public function usuariosUpdate(Request $request, $id)
    {
        try {
            // Preparamos los datos a actualizar adaptándonos a los campos del formulario
            $datosActualizacion = [
                'usu_dni' => $request->input('dni') ?? $request->input('usu_dni'),
                'usu_nombres' => $request->input('nombres') ?? $request->input('usu_nombres'),
                'usu_apellidos' => $request->input('apellidos') ?? $request->input('usu_apellidos'),
                'usu_correo' => $request->input('correo') ?? $request->input('usu_correo'),
                'rol_id' => $request->input('rol_id'),
            ];

            // Si el usuario ingresó una nueva contraseña, la actualizamos cifrada; si no, la omitimos
            if ($request->filled('password')) {
                $datosActualizacion['password'] = bcrypt($request->input('password'));
            }

            // Ejecutamos la actualización basada en el identificador único (usu_id)
            DB::table('bd_usuarios')
                ->where('usu_id', $id)
                ->update($datosActualizacion);

            return back()->with('success', '¡Usuario actualizado correctamente!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    public function dashboardIndex()
    {
        // 1. Total de activos en el padrón maestro
        $totalActivos = DB::table('bd_activos_maestro')->count();

        // 2. Activos verificados en el censo actual (ej. año 2026 o último censo)
        $activosVerificados = DB::table('cen_censos')
            ->whereYear('cer_fecha', 2026)
            ->distinct('act_id')
            ->count('act_id');

        // Cobertura de verificación
        $cobertura = $totalActivos > 0 ? round(($activosVerificados / $totalActivos) * 100) : 0;
        $pendientes = $totalActivos - $activosVerificados;

        // 3. Alertas de movimiento / trazabilidad (ej: eventos registrados en la bitácora)
        $alertasNoEncontradas = DB::table('aud_bitacora_trazabilidad')
            ->where('aud_accion', 'NO_ENCONTRADO')
            ->count();

        $alertasMovimiento = DB::table('aud_bitacora_trazabilidad')
            ->where('aud_accion', 'CAMBIO_UBICACION')
            ->count();

        // Detalle de últimas alertas para la tabla de trazabilidad
        $detalleAlertas = DB::table('aud_bitacora_trazabilidad')
            ->join('bd_activos_maestro', 'aud_bitacora_trazabilidad.act_id', '=', 'bd_activos_maestro.act_id')
            ->select(
                'bd_activos_maestro.act_codigo as codigo',
                'bd_activos_maestro.act_denominacion as activo',
                'aud_bitacora_trazabilidad.aud_detalle as movimiento',
                'aud_bitacora_trazabilidad.created_at as fecha'
            )
            ->orderBy('aud_bitacora_trazabilidad.created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalActivos',
            'activosVerificados',
            'cobertura',
            'pendientes',
            'alertasNoEncontradas',
            'alertasMovimiento',
            'detalleAlertas'
        ));
    }

    /**
     * Alterna el estado de un usuario (Activar / Desactivar).
     */
    public function usuariosDestroy($id)
    {
        try {
            // Evitar que el usuario administrador cambie el estado de su propia cuenta
            if (auth()->check() && auth()->id() == $id) {
                return back()->with('error', 'No puedes cambiar el estado de tu propia cuenta mientras estás en sesión.');
            }

            // Buscamos el usuario actual
            $usuario = DB::table('bd_usuarios')->where('usu_id', $id)->first();

            if (!$usuario) {
                return back()->with('error', 'Usuario no encontrado.');
            }

            // Invertimos el estado lógico (Si es 1 pasa a 0, si es 0 pasa a 1)
            $nuevoEstado = $usuario->usu_activo == 1 ? 0 : 1;
            
            // Mensaje dinámico según la acción realizada
            $mensaje = $nuevoEstado == 1 ? '¡Usuario reactivado correctamente!' : '¡Usuario desactivado correctamente!';

            // Actualizamos la BD
            DB::table('bd_usuarios')
                ->where('usu_id', $id)
                ->update(['usu_activo' => $nuevoEstado]);

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al cambiar el estado del usuario: ' . $e->getMessage());
        }
    }
}