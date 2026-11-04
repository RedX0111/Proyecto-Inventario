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
            $busqueda = trim($request->input('buscar'));
            $palabras = explode(' ', $busqueda);

            $query->where(function($q) use ($palabras) {
                foreach ($palabras as $palabra) {
                    if (empty(trim($palabra))) continue;

                    $q->where(function($subQ) use ($palabra) {
                        $subQ->where('codigo_patrimonial', 'ILIKE', "%{$palabra}%")
                             ->orWhere('denominacion', 'ILIKE', "%{$palabra}%")
                             ->orWhere('serie', 'ILIKE', "%{$palabra}%")
                             ->orWhere('marca', 'ILIKE', "%{$palabra}%")
                             ->orWhere('modelo', 'ILIKE', "%{$palabra}%");
                    });
                }
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
                // EVITAR ERROR: Ignorar filas en blanco generadas por Google Sheets
                if (empty($item['codigo'])) {
                    continue; 
                }

                DB::table('bd_staging_censo')->insert([
                    // SANITIZACIÓN POSTGRESQL: Forzar los tipos de datos correctos
                    'stg_orden' => !empty($item['orden']) ? (int) $item['orden'] : null,
                    'codigo_patrimonial' => (string) $item['codigo'],
                    'denominacion' => !empty($item['denominacion']) ? (string) $item['denominacion'] : 'SIN DENOMINACION',
                    'marca' => $item['marca'] ?? '',
                    'modelo' => $item['modelo'] ?? '',
                    'tipo' => $item['tipo'] ?? '',
                    'color' => $item['color'] ?? '',
                    'serie' => $item['serie'] ?? '',
                    'dimensiones' => $item['dimensiones'] ?? 'SIN DIMENSIONES',
                    'fecha_censo' => !empty($item['fecha']) ? $item['fecha'] : now()->toDateString(),
                    'anio_auditoria' => !empty($item['anio_auditoria']) ? (int) $item['anio_auditoria'] : (int) date('Y'),
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
            dd("Fallo SQL al JALAR los datos:", $e->getMessage()); 
        }
    }

    /**
     * Aprueba masivamente los registros pendientes de Staging hacia el Padrón Maestro.
     */
    public function aprobarLote()
    {
        try {
            DB::beginTransaction();

            $registros = DB::table('bd_staging_censo')->get();

            if ($registros->isEmpty()) {
                return back()->with('error', 'No hay datos en el lote para aprobar.');
            }

            // Diccionario temporal para guardar los IDs de los censos por año y no saturar la BD
            $cacheCensos = []; 

            // 1. Procesar cada registro
            foreach ($registros as $reg) {
                
                // A) Determinar el periodo y censo dinámicamente según la data de Sheets
                $anio = $reg->anio_auditoria ?? date('Y', strtotime($reg->fecha_censo ?? now()));

                if (!isset($cacheCensos[$anio])) {
                    $periodo = DB::table('bd_periodos_fiscales')->where('per_anio', $anio)->first();
                    
                    $per_id = $periodo ? $periodo->per_id : DB::table('bd_periodos_fiscales')->insertGetId([
                        'per_anio' => $anio,
                        'per_cerrado' => 0
                    ], 'per_id');

                    $censo = DB::table('cen_censos')->where('per_id', $per_id)->first();
                    
                    $cer_id = $censo ? $censo->cer_id : DB::table('cen_censos')->insertGetId([
                        'per_id' => $per_id,
                        'cer_fecha' => $reg->fecha_censo ?? now()->toDateString(),
                        'cer_observaciones' => "Censo importado de $anio"
                    ], 'cer_id');

                    $cacheCensos[$anio] = $cer_id;
                }

                $cer_id = $cacheCensos[$anio];

                // B) Normalizar Marca
                $mar_nombre = trim($reg->marca) ?: 'SIN MARCA';
                $marca = DB::table('mar_marcas')->where('mar_nombre', 'ILIKE', $mar_nombre)->first();
                $mar_id = $marca ? $marca->mar_id : DB::table('mar_marcas')->insertGetId(['mar_nombre' => $mar_nombre], 'mar_id');

                // C) Normalizar Modelo
                $mod_nombre = trim($reg->modelo) ?: 'SIN MODELO';
                $modelo = DB::table('mod_modelos')->where('mod_nombre', 'ILIKE', $mod_nombre)->where('mar_id', $mar_id)->first();
                $mod_id = $modelo ? $modelo->mod_id : DB::table('mod_modelos')->insertGetId(['mod_nombre' => $mod_nombre, 'mar_id' => $mar_id], 'mod_id');

                // D) Normalizar Color
                $col_nombre = trim($reg->color) ?: 'SIN COLOR';
                $color = DB::table('col_colores')->where('col_nombre', 'ILIKE', $col_nombre)->first();
                $col_id = $color ? $color->col_id : DB::table('col_colores')->insertGetId(['col_nombre' => $col_nombre], 'col_id');

                // E) Normalizar Tipo/Clasificador
                $cla_nombre = trim($reg->tipo) ?: 'SIN TIPO';
                $clasificador = DB::table('sla_clasificadores')->where('cla_descripcion', 'ILIKE', $cla_nombre)->first();
                $cla_id = $clasificador ? $clasificador->cla_id : DB::table('sla_clasificadores')->insertGetId(['cla_descripcion' => $cla_nombre], 'cla_id');

                // F) Normalizar Ubicación
                $ubi_nombre = trim($reg->ubicacion) ?: 'GENERAL';
                $ubicacion = DB::table('bd_ubicaciones')->where('ubi_nombre', 'ILIKE', $ubi_nombre)->first();
                $ubi_id = $ubicacion ? $ubicacion->ubi_id : DB::table('bd_ubicaciones')->insertGetId([
                    'ubi_nombre' => $ubi_nombre,
                    'ubi_codigo' => uniqid('U-') 
                ], 'ubi_id');

                // G) Normalizar Estado Físico
                $est_nombre = trim($reg->estado) ?: 'BUENO';
                $estado = DB::table('bd_estados_fisicos')->where('est_nombre', 'ILIKE', $est_nombre)->first();
                $est_id = $estado ? $estado->est_id : DB::table('bd_estados_fisicos')->insertGetId(['est_nombre' => $est_nombre], 'est_id');

                // H) Actualizar o Insertar el Activo
                DB::table('bd_activos_maestro')->updateOrInsert(
                    ['act_codigo' => $reg->codigo_patrimonial],
                    [
                        'act_denominacion' => $reg->denominacion,
                        'act_serie' => $reg->serie,
                        'act_dimensiones' => $reg->dimensiones,
                        'mod_id' => $mod_id,
                        'col_id' => $col_id,
                        'cla_id' => $cla_id,
                        'updated_at' => now(),
                    ]
                );

                $activo = DB::table('bd_activos_maestro')->where('act_codigo', $reg->codigo_patrimonial)->first();

                // I) Vincular el Activo al Censo dinámico
                DB::table('bd_censo_campo')->updateOrInsert(
                    ['cer_id' => $cer_id, 'act_id' => $activo->act_id],
                    [
                        'est_id' => $est_id,
                        'ubi_id' => $ubi_id,
                        'cdk_estado_limpieza' => $reg->constatado_actual ?? 'CENSADO',
                        'cdk_etiqueta_estado' => $reg->alerta_movimiento ?? 'SIN VARIACIÓN'
                    ]
                );
            }

            // 2. Limpiar Staging
            DB::table('bd_staging_censo')->truncate();

            DB::commit();
            return back()->with('success', '¡Lote aprobado, normalizado y enlazado exitosamente al periodo correspondiente!');

        } catch (\Exception $e) {
            DB::rollBack();
            dd("Error de Normalización:", $e->getMessage(), "Línea: " . $e->getLine());
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
     * Registra un activo patrimonial individual de forma manual.
     */
    public function activosStore(Request $request)
    {
        // 1. Validar que el código no exista ya en la base de datos
        $request->validate([
            'codigo_patrimonial' => 'required|string|unique:bd_activos_maestro,act_codigo|max:50',
            'denominacion' => 'required|string|max:255',
            'mod_id' => 'nullable|integer',
            'col_id' => 'nullable|integer',
            'cla_id' => 'nullable|integer',
            'ubi_id' => 'nullable|integer',
            'est_id' => 'nullable|integer',
            'serie' => 'nullable|string|max:100',
            'dimensiones' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $codigo = $request->input('codigo_patrimonial');

            // 2. Insertar en el padrón maestro (Recordando el 2do parámetro 'act_id' para PostgreSQL)
            $act_id = DB::table('bd_activos_maestro')->insertGetId([
                'act_codigo' => $codigo,
                'act_denominacion' => $request->input('denominacion'),
                'act_serie' => $request->input('serie', 'SIN SERIE'),
                'act_dimensiones' => $request->input('dimensiones', 'SIN DIMENSIONES'),
                'mod_id' => $request->input('mod_id'),
                'col_id' => $request->input('col_id'),
                'cla_id' => $request->input('cla_id'),
                'act_es_temporal' => $request->input('es_temporal', 0), // Útil si quieres marcarlo como "Sobrante" (1)
                'created_at' => now(),
                'updated_at' => now(),
            ], 'act_id');

            // 3. Registrar la acción en la bitácora de trazabilidad
            DB::table('aud_bitacora_trazabilidad')->insert([
                'act_id' => $act_id,
                'usu_id' => auth()->id() ?? 1,
                'aud_accion' => 'CREACION MANUAL',
                'aud_detalle' => "Ingreso manual del activo patrimonial {$codigo}",
                'created_at' => now(),
            ]);

            // 4. Vincular el activo al periodo fiscal abierto para que aparezca en el Dashboard
            $periodo = DB::table('bd_periodos_fiscales')->where('per_cerrado', 0)->first();
            
            if ($periodo) {
                $censo = DB::table('cen_censos')->where('per_id', $periodo->per_id)->first();
                
                $cer_id = $censo ? $censo->cer_id : DB::table('cen_censos')->insertGetId([
                    'per_id' => $periodo->per_id,
                    'cer_fecha' => now()->toDateString(),
                    'cer_observaciones' => 'Generado por ingreso manual'
                ], 'cer_id');

                DB::table('bd_censo_campo')->insert([
                    'cer_id' => $cer_id,
                    'act_id' => $act_id,
                    'est_id' => $request->input('est_id'),
                    'ubi_id' => $request->input('ubi_id'),
                    'cdk_estado_limpieza' => 'CENSADO', // Da por hecho que ya está verificado
                    'cdk_etiqueta_estado' => 'NUEVO REGISTRO'
                ]);
            }

            DB::commit();
            return back()->with('success', "¡Activo {$codigo} registrado y vinculado al censo exitosamente!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al registrar el activo: ' . $e->getMessage());
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

    /**
     * Muestra el panel de gestión de catálogos (Marcas, Colores, Tipos, Denominaciones, etc.)
     */
    public function catalogosIndex()
    {
        $denominaciones = DB::table('bd_activos_maestro')
                            ->select('act_denominacion as nombre')
                            ->distinct()
                            ->orderBy('act_denominacion')
                            ->get(); // O puedes crear una tabla catálogo específica si lo prefieres

        $marcas = DB::table('mar_marcas')->orderBy('mar_nombre')->get();
        $colores = DB::table('col_colores')->orderBy('col_nombre')->get();
        $clasificadores = DB::table('sla_clasificadores')->orderBy('cla_descripcion')->get();
        $ubicaciones = DB::table('bd_ubicaciones')->orderBy('ubi_nombre')->get();
        $estados = DB::table('bd_estados_fisicos')->orderBy('est_nombre')->get();

        return view('admin.catalogos', compact('denominaciones', 'marcas', 'colores', 'clasificadores', 'ubicaciones', 'estados'));
    }

    /**
     * Actualiza el nombre de un elemento del catálogo existente.
     */
    public function catalogosUpdate(Request $request)
    {
        $tipo = $request->input('tipo_catalogo');
        $id = $request->input('id');
        $nuevoNombre = strtoupper(trim($request->input('nombre')));

        if (empty($nuevoNombre)) {
            return back()->with('error', 'El nombre no puede estar vacío.');
        }

        try {
            if ($tipo === 'denominacion') {
                // Las denominaciones se actualizan directamente en los activos que las poseían
                $viejoNombre = $request->input('viejo_nombre');
                DB::table('bd_activos_maestro')
                    ->where('act_denominacion', $viejoNombre)
                    ->update(['act_denominacion' => $nuevoNombre, 'updated_at' => now()]);
            } elseif ($tipo === 'marca') {
                DB::table('mar_marcas')->where('mar_id', $id)->update(['mar_nombre' => $nuevoNombre]);
            } elseif ($tipo === 'color') {
                DB::table('col_colores')->where('col_id', $id)->update(['col_nombre' => $nuevoNombre]);
            } elseif ($tipo === 'tipo') {
                DB::table('sla_clasificadores')->where('cla_id', $id)->update(['cla_descripcion' => $nuevoNombre]);
            } elseif ($tipo === 'ubicacion') {
                DB::table('bd_ubicaciones')->where('ubi_id', $id)->update(['ubi_nombre' => $nuevoNombre]);
            }

            return back()->with('success', '¡Registro actualizado exitosamente!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Guarda un nuevo registro en el catálogo correspondiente.
     */
    public function catalogosStore(Request $request)
    {
        $tipo = $request->input('tipo_catalogo');
        $nombre = strtoupper(trim($request->input('nombre')));

        if (empty($nombre)) {
            return back()->with('error', 'El nombre no puede estar vacío.');
        }

        try {
            if ($tipo === 'marca') {
                DB::table('mar_marcas')->insert(['mar_nombre' => $nombre]);
            } elseif ($tipo === 'color') {
                DB::table('col_colores')->insert(['col_nombre' => $nombre]);
            } elseif ($tipo === 'tipo') {
                DB::table('sla_clasificadores')->insert(['cla_descripcion' => $nombre]);
            } elseif ($tipo === 'estado') {
                DB::table('bd_estados_fisicos')->insert(['est_nombre' => $nombre]);
            } elseif ($tipo === 'ubicacion') {
                DB::table('bd_ubicaciones')->insert([
                    'ubi_nombre' => $nombre,
                    'ubi_codigo' => uniqid('U-')
                ]);
            }

            return back()->with('success', '¡Nuevo registro agregado al catálogo exitosamente!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al guardar (Es posible que el nombre ya exista). Detalle: ' . $e->getMessage());
        }
    }
}