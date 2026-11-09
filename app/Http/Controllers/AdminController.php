<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

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

        // 6. Gráfico: Distribución por Tipo de Activo
        $tiposActivos = DB::table('v_bd_tabla_master')
            ->where('anio_auditoria', $anioSeleccionado)
            ->whereNotNull('tipo')
            ->where('tipo', '!=', '')
            ->select('tipo', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo')
            ->orderBy('total', 'desc')
            ->limit(7)
            ->get();

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
                if (empty($item['codigo'])) {
                    continue; 
                }

                DB::table('bd_staging_censo')->insert([
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

            $cacheCensos = []; 

            foreach ($registros as $reg) {
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

                $mar_nombre = trim($reg->marca) ?: 'SIN MARCA';
                $marca = DB::table('mar_marcas')->where('mar_nombre', 'ILIKE', $mar_nombre)->first();
                $mar_id = $marca ? $marca->mar_id : DB::table('mar_marcas')->insertGetId(['mar_nombre' => $mar_nombre], 'mar_id');

                $mod_nombre = trim($reg->modelo) ?: 'SIN MODELO';
                $modelo = DB::table('mod_modelos')->where('mod_nombre', 'ILIKE', $mod_nombre)->where('mar_id', $mar_id)->first();
                $mod_id = $modelo ? $modelo->mod_id : DB::table('mod_modelos')->insertGetId(['mod_nombre' => $mod_nombre, 'mar_id' => $mar_id], 'mod_id');

                $col_nombre = trim($reg->color) ?: 'SIN COLOR';
                $color = DB::table('col_colores')->where('col_nombre', 'ILIKE', $col_nombre)->first();
                $col_id = $color ? $color->col_id : DB::table('col_colores')->insertGetId(['col_nombre' => $col_nombre], 'col_id');

                $cla_nombre = trim($reg->tipo) ?: 'SIN TIPO';
                $clasificador = DB::table('sla_clasificadores')->where('cla_descripcion', 'ILIKE', $cla_nombre)->first();
                $cla_id = $clasificador ? $clasificador->cla_id : DB::table('sla_clasificadores')->insertGetId(['cla_descripcion' => $cla_nombre], 'cla_id');

                $ubi_nombre = trim($reg->ubicacion) ?: 'GENERAL';
                $ubicacion = DB::table('bd_ubicaciones')->where('ubi_nombre', 'ILIKE', $ubi_nombre)->first();
                $ubi_id = $ubicacion ? $ubicacion->ubi_id : DB::table('bd_ubicaciones')->insertGetId([
                    'ubi_nombre' => $ubi_nombre,
                    'ubi_codigo' => uniqid('U-') 
                ], 'ubi_id');

                $est_nombre = trim($reg->estado) ?: 'BUENO';
                $estado = DB::table('bd_estados_fisicos')->where('est_nombre', 'ILIKE', $est_nombre)->first();
                $est_id = $estado ? $estado->est_id : DB::table('bd_estados_fisicos')->insertGetId(['est_nombre' => $est_nombre], 'est_id');

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
            $periodo = DB::table('bd_periodos_fiscales')->where('per_id', $id)->first();

            if (!$periodo) {
                return back()->with('error', 'El período no existe.');
            }

            if ($periodo->per_cerrado == 1) {
                return back()->with('error', 'Acción denegada: Este período ya fue cerrado el ' . $periodo->per_fecha_cierre . ' y no puede modificarse.');
            }

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
     * Actualiza un activo patrimonial y toda su información relacionada desde el modal del dashboard.
     */
    public function activosUpdate(Request $request, $codigo)
    {
        $request->validate([
            'denominacion' => 'required|string|max:255',
            'serie'        => 'nullable|string|max:100',
            'dimensiones'  => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            $activo = DB::table('bd_activos_maestro')->where('act_codigo', $codigo)->first();

            if (!$activo) {
                return back()->with('error', "No se encontró el activo con código {$codigo}.");
            }

            // 1. Verificar relación Marca -> Modelo si el usuario cambió la marca
            $modId = $request->input('mod_id') ?: $activo->mod_id;
            if ($request->filled('mar_id') && $modId) {
                DB::table('mod_modelos')
                    ->where('mod_id', $modId)
                    ->update(['mar_id' => $request->input('mar_id')]);
            }

            // 2. Actualizar datos maestros en bd_activos_maestro
            DB::table('bd_activos_maestro')
                ->where('act_id', $activo->act_id)
                ->update([
                    'act_denominacion' => strtoupper(trim($request->input('denominacion'))),
                    'act_serie'        => strtoupper(trim($request->input('serie', 'SIN SERIE'))),
                    'act_dimensiones'  => strtoupper(trim($request->input('dimensiones', 'SIN DIMENSIONES'))),
                    'mod_id'           => $modId,
                    'col_id'           => $request->input('col_id') ?: $activo->col_id,
                    'cla_id'           => $request->input('cla_id') ?: $activo->cla_id,
                    'updated_at'       => now(),
                ]);

            // 3. Actualizar datos de ubicación, estado físico y constatación en bd_censo_campo
            $datosCenso = [];
            if ($request->filled('est_id')) {
                $datosCenso['est_id'] = $request->input('est_id');
            }
            if ($request->filled('ubi_id')) {
                $datosCenso['ubi_id'] = $request->input('ubi_id');
            }
            if ($request->filled('constatado_actual')) {
                $datosCenso['cdk_estado_limpieza'] = strtoupper(trim($request->input('constatado_actual')));
            }
            if ($request->filled('alerta_movimiento')) {
                $datosCenso['cdk_etiqueta_estado'] = strtoupper(trim($request->input('alerta_movimiento')));
            }

            if (!empty($datosCenso)) {
                DB::table('bd_censo_campo')
                    ->where('act_id', $activo->act_id)
                    ->update($datosCenso);
            }

            // 4. Registrar la modificación completa en la bitácora de trazabilidad
            DB::table('aud_bitacora_trazabilidad')->insert([
                'act_id'      => $activo->act_id,
                'usu_id'      => auth()->id() ?? 1,
                'aud_accion'  => 'ACTUALIZACION INTEGRAL',
                'aud_detalle' => "Actualización completa del activo {$codigo} (Denominación, Serie, Catálogos y Estado/Ubicación).",
                'created_at'  => now(),
            ]);

            DB::commit();
            return back()->with('success', "¡Activo {$codigo} y todos sus datos relacionados fueron actualizados correctamente!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar el activo: ' . $e->getMessage());
        }
    }

    /**
     * Registra un activo patrimonial individual de forma manual.
     */
    public function activosStore(Request $request)
    {
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

            $act_id = DB::table('bd_activos_maestro')->insertGetId([
                'act_codigo' => $codigo,
                'act_denominacion' => $request->input('denominacion'),
                'act_serie' => $request->input('serie', 'SIN SERIE'),
                'act_dimensiones' => $request->input('dimensiones', 'SIN DIMENSIONES'),
                'mod_id' => $request->input('mod_id'),
                'col_id' => $request->input('col_id'),
                'cla_id' => $request->input('cla_id'),
                'act_es_temporal' => $request->input('es_temporal', 0),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'act_id');

            DB::table('aud_bitacora_trazabilidad')->insert([
                'act_id' => $act_id,
                'usu_id' => auth()->id() ?? 1,
                'aud_accion' => 'CREACION MANUAL',
                'aud_detalle' => "Ingreso manual del activo patrimonial {$codigo}",
                'created_at' => now(),
            ]);

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
                    'cdk_estado_limpieza' => 'CENSADO',
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
            $datosActualizacion = [
                'usu_dni' => $request->input('dni') ?? $request->input('usu_dni'),
                'usu_nombres' => $request->input('nombres') ?? $request->input('usu_nombres'),
                'usu_apellidos' => $request->input('apellidos') ?? $request->input('usu_apellidos'),
                'usu_correo' => $request->input('correo') ?? $request->input('usu_correo'),
                'rol_id' => $request->input('rol_id'),
            ];

            if ($request->filled('password')) {
                $datosActualizacion['password'] = bcrypt($request->input('password'));
            }

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
        $totalActivos = DB::table('bd_activos_maestro')->count();

        $activosVerificados = DB::table('cen_censos')
            ->whereYear('cer_fecha', 2026)
            ->distinct('act_id')
            ->count('act_id');

        $cobertura = $totalActivos > 0 ? round(($activosVerificados / $totalActivos) * 100) : 0;
        $pendientes = $totalActivos - $activosVerificados;

        $alertasNoEncontradas = DB::table('aud_bitacora_trazabilidad')
            ->where('aud_accion', 'NO_ENCONTRADO')
            ->count();

        $alertasMovimiento = DB::table('aud_bitacora_trazabilidad')
            ->where('aud_accion', 'CAMBIO_UBICACION')
            ->count();

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
            if (auth()->check() && auth()->id() == $id) {
                return back()->with('error', 'No puedes cambiar el estado de tu propia cuenta mientras estás en sesión.');
            }

            $usuario = DB::table('bd_usuarios')->where('usu_id', $id)->first();

            if (!$usuario) {
                return back()->with('error', 'Usuario no encontrado.');
            }

            $nuevoEstado = $usuario->usu_activo == 1 ? 0 : 1;
            $mensaje = $nuevoEstado == 1 ? '¡Usuario reactivado correctamente!' : '¡Usuario desactivado correctamente!';

            DB::table('bd_usuarios')
                ->where('usu_id', $id)
                ->update(['usu_activo' => $nuevoEstado]);

            return back()->with('success', $mensaje);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al cambiar el estado del usuario: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el panel de gestión de catálogos.
     */
    public function catalogosIndex()
    {
        $denominaciones = DB::table('bd_activos_maestro')
                            ->select('act_denominacion as nombre')
                            ->distinct()
                            ->orderBy('act_denominacion')
                            ->get();

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

    /**
     * Muestra la interfaz de configuración de reportes.
     */
    public function reportesConfig(Request $request)
    {
        $periodos = DB::table('bd_periodos_fiscales')->orderBy('per_anio', 'desc')->get();
        $ubicaciones = DB::table('bd_ubicaciones')->orderBy('ubi_nombre')->get();
        $estados = DB::table('bd_estados_fisicos')->orderBy('est_nombre')->get();
        $clasificadores = DB::table('sla_clasificadores')->orderBy('cla_descripcion')->get();

        return view('admin.reportes_config', compact('periodos', 'ubicaciones', 'estados', 'clasificadores'));
    }

    /**
     * Procesa los filtros y muestra la vista de impresión/PDF nativa.
     */
    public function reportesGenerar(Request $request)
    {
        $query = DB::table('v_bd_tabla_master');

        // 1. Aplicar filtros dinámicos seleccionados por el usuario
        if ($request->filled('periodo')) {
            $anio = DB::table('bd_periodos_fiscales')->where('per_id', $request->input('periodo'))->value('per_anio');
            $query->where('anio_auditoria', $anio);
        }
        if ($request->filled('ubicacion')) {
            $query->where('ubicacion', $request->input('ubicacion'));
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }
        if ($request->filled('constatado')) {
            $query->where('constatado_actual', $request->input('constatado'));
        }
        if ($request->filled('alerta')) {
            $query->where('alerta_movimiento', $request->input('alerta'));
        }

        $registros = $query->get();
        $columnasSeleccionadas = $request->input('columnas', [
            'codigo_patrimonial', 'denominacion', 'marca', 'serie', 'ubicacion', 'estado', 'constatado_actual'
        ]);
        
        $formato = $request->input('formato', 'pantalla');

        // Si el usuario elige exportar a Excel / CSV
        if ($formato === 'excel') {
            $filename = "reporte_patrimonial_" . date('Y-m-d') . ".csv";
            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=$filename",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $callback = function() use ($registros, $columnasSeleccionadas) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // Soporte tildes UTF-8 Excel
                
                fputcsv($file, array_map('ucfirst', $columnasSeleccionadas), ';');

                foreach ($registros as $reg) {
                    $fila = [];
                    foreach ($columnasSeleccionadas as $col) {
                        $fila[] = $reg->{$col} ?? '-';
                    }
                    fputcsv($file, $fila, ';');
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // Si elige Vista Previa / Guardar PDF (Nativo mediante motor del navegador)
        return view('admin.reportes_print', compact('registros', 'columnasSeleccionadas'));
    }
    
    public function validarPatrimonioIndex(Request $request)
    {
        $resultadosValidacion = null;

        if ($request->hasFile('archivo_csv')) {
            $file = $request->file('archivo_csv');
            $totalFilas = 0;
            $actualizados = 0;
            $errores = [];
            
            if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
                $header = fgetcsv($handle, 1000, ","); // Leer cabeceras del CSV
                
                // Normalizamos cabeceras (minúsculas y sin espacios)
                $header = array_map(function($h) {
                    return strtolower(trim(str_replace("\xEF\xBB\xBF", "", $h))); // Remueve BOM si existe
                }, $header);

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) >= count($header)) {
                        $fila = array_combine(array_slice($header, 0, count($data)), $data);
                        $totalFilas++;

                        $codigo = trim($fila['codigo_patrimonial'] ?? '');
                        
                        if (empty($codigo)) {
                            $errores[] = "Fila {$totalFilas}: El código patrimonial está vacío en el CSV.";
                            continue;
                        }

                        // Buscar el activo en el padrón maestro
                        $activoBD = DB::table('bd_activos_maestro')->where('act_codigo', $codigo)->first();

                        if (!$activoBD) {
                            $errores[] = "Fila {$totalFilas}: El código '{$codigo}' no existe en el padrón maestro.";
                        } else {
                            try {
                                DB::beginTransaction();

                                // Preparamos los datos nuevos del CSV (SIN MODIFICAR LA DENOMINACIÓN)
                                $nuevaSerie = strtoupper(trim($fila['serie'] ?? $activoBD->act_serie));
                                $nuevaMarca = strtoupper(trim($fila['marca'] ?? ''));
                                $nuevoModelo = strtoupper(trim($fila['modelo'] ?? ''));
                                $nuevoColor = strtoupper(trim($fila['color'] ?? ''));
                                $nuevaUbicacion = strtoupper(trim($fila['ubicacion'] ?? ''));
                                $nuevoEstado = strtoupper(trim($fila['estado'] ?? ''));

                                // 1. Obtener la marca a través del modelo actual del activo (si existe)
                                $modeloActual = DB::table('mod_modelos')->where('mod_id', $activoBD->mod_id)->first();
                                $marId = $modeloActual ? $modeloActual->mar_id : null;

                                if (!empty($nuevaMarca)) {
                                    $m = DB::table('mar_marcas')->where('mar_nombre', 'ILIKE', $nuevaMarca)->first();
                                    $marId = $m ? $m->mar_id : DB::table('mar_marcas')->insertGetId(['mar_nombre' => $nuevaMarca], 'mar_id');
                                }

                                $modId = $activoBD->mod_id;
                                if (!empty($nuevoModelo) || $marId !== ($modeloActual ? $modeloActual->mar_id : null)) {
                                    $nombreMod = !empty($nuevoModelo) ? $nuevoModelo : ($modeloActual ? $modeloActual->mod_nombre : 'SIN MODELO');
                                    
                                    $mo = DB::table('mod_modelos')
                                        ->where('mod_nombre', 'ILIKE', $nombreMod)
                                        ->where('mar_id', $marId)
                                        ->first();
                                        
                                    $modId = $mo ? $mo->mod_id : DB::table('mod_modelos')->insertGetId([
                                        'mod_nombre' => $nombreMod, 
                                        'mar_id' => $marId
                                    ], 'mod_id');
                                }

                                $colId = $activoBD->col_id;
                                if (!empty($nuevoColor)) {
                                    $c = DB::table('col_colores')->where('col_nombre', 'ILIKE', $nuevoColor)->first();
                                    $colId = $c ? $c->col_id : DB::table('col_colores')->insertGetId(['col_nombre' => $nuevoColor], 'col_id');
                                }

                                // Buscamos la relación transaccional actual en censo_campo
                                $censoCampoActual = DB::table('bd_censo_campo')->where('act_id', $activoBD->act_id)->first();
                                $ubiId = $censoCampoActual ? $censoCampoActual->ubi_id : null;
                                
                                if (!empty($nuevaUbicacion)) {
                                    $u = DB::table('bd_ubicaciones')->where('ubi_nombre', 'ILIKE', $nuevaUbicacion)->first();
                                    $ubiId = $u ? $u->ubi_id : DB::table('bd_ubicaciones')->insertGetId([
                                        'ubi_nombre' => $nuevaUbicacion, 
                                        'ubi_codigo' => uniqid('UB-')
                                    ], 'ubi_id');
                                }

                                $estId = $censoCampoActual ? $censoCampoActual->est_id : null;
                                if (!empty($nuevoEstado)) {
                                    $e = DB::table('bd_estados_fisicos')->where('est_nombre', 'ILIKE', $nuevoEstado)->first();
                                    $estId = $e ? $e->est_id : DB::table('bd_estados_fisicos')->insertGetId(['est_nombre' => $nuevoEstado], 'est_id');
                                }

                                // 2. Actualizar el padrón maestro (Respetando act_denominacion actual)
                                DB::table('bd_activos_maestro')
                                    ->where('act_codigo', $codigo)
                                    ->update([
                                        'act_serie' => $nuevaSerie,
                                        'mod_id' => $modId,
                                        'col_id' => $colId,
                                        'updated_at' => now(),
                                    ]);

                                // 3. Actualizar la relación transaccional de campo (ubicación y estado) si existe registro
                                if ($censoCampoActual) {
                                    DB::table('bd_censo_campo')
                                        ->where('act_id', $activoBD->act_id)
                                        ->update([
                                            'ubi_id' => $ubiId,
                                            'est_id' => $estId,
                                        ]);
                                }

                                // 4. Registrar en la bitácora de trazabilidad
                                DB::table('aud_bitacora_trazabilidad')->insert([
                                    'act_id' => $activoBD->act_id,
                                    'usu_id' => auth()->id() ?? 1,
                                    'aud_accion' => 'VALIDACION Y ACTUALIZACION CSV',
                                    'aud_detalle' => "Datos actualizados mediante validación masiva CSV (Código: {$codigo})",
                                    'created_at' => now(),
                                ]);

                                DB::commit();
                                $actualizados++;

                            } catch (\Exception $e) {
                                DB::rollBack();
                                $errores[] = "Fila {$totalFilas} (Código {$codigo}): Error al actualizar -> " . $e->getMessage();
                            }
                        }
                    }
                }
                fclose($handle);
                
                $resultadosValidacion = [
                    'total' => $totalFilas,
                    'actualizados' => $actualizados,
                    'errores' => $errores
                ];
            }
        }

        $columnasRequeridasInfo = [
            ['columna' => 'codigo_patrimonial', 'descripcion' => 'Código único de 12 dígitos asignado por la SBN (Clave de búsqueda obligatoria).', 'obligatorio' => 'Sí'],
            ['columna' => 'denominacion', 'descripcion' => 'Nombre oficial (Se omite su cambio automático para preservar el catálogo original).', 'obligatorio' => 'Informativo'],
            ['columna' => 'marca', 'descripcion' => 'Fabricante o marca comercial del bien (Se actualizará o registrará automáticamente).', 'obligatorio' => 'Sí'],
            ['columna' => 'modelo', 'descripcion' => 'Modelo o versión del equipo.', 'obligatorio' => 'Recomendado'],
            ['columna' => 'serie', 'descripcion' => 'Número de serie de fábrica.', 'obligatorio' => 'Condicional'],
            ['columna' => 'color', 'descripcion' => 'Color predominante del bien.', 'obligatorio' => 'Sí'],
            ['columna' => 'ubicacion', 'descripcion' => 'Ambiente físico asignado (Actualiza la ubicación en campo).', 'obligatorio' => 'Sí'],
            ['columna' => 'estado', 'descripcion' => 'Estado físico actual (Bueno, Regular, Malo).', 'obligatorio' => 'Sí']
        ];

        return view('admin.validar_patrimonio', compact('columnasRequeridasInfo', 'resultadosValidacion'));
    }

    /**
     * Procesa la actualización o completado de datos faltantes para cumplir con Patrimonio.
     */
    public function validarPatrimonioProcesar(Request $request)
    {
        $codigo = $request->input('codigo_patrimonial');

        $request->validate([
            'codigo_patrimonial' => 'required|string',
            'denominacion' => 'required|string|max:255',
            'serie' => 'nullable|string|max:100',
            'dimensiones' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            DB::table('bd_activos_maestro')
                ->where('act_codigo', $codigo)
                ->update([
                    'act_denominacion' => strtoupper(trim($request->input('denominacion'))),
                    'act_serie' => strtoupper(trim($request->input('serie', 'SIN SERIE'))),
                    'act_dimensiones' => strtoupper(trim($request->input('dimensiones', 'SIN DIMENSIONES'))),
                    'mod_id' => $request->input('mod_id'),
                    'col_id' => $request->input('col_id'),
                    'cla_id' => $request->input('cla_id'),
                    'updated_at' => now(),
                ]);

            DB::table('aud_bitacora_trazabilidad')->insert([
                'act_id' => DB::table('bd_activos_maestro')->where('act_codigo', $codigo)->value('act_id'),
                'usu_id' => auth()->id() ?? 1,
                'aud_accion' => 'VALIDACION PATRIMONIO',
                'aud_detalle' => "Completado/Validación de datos obligatorios para el activo {$codigo}",
                'created_at' => now(),
            ]);

            DB::commit();
            return back()->with('success', "¡Activo {$codigo} validado y actualizado correctamente!");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al validar el activo: ' . $e->getMessage());
        }
    }
}