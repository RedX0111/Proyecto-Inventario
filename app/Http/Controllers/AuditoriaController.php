<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AuditoriaController extends Controller
{
    /**
     * Muestra la bandeja de registros PENDIENTES que llegaron desde Google Sheets.
     */
    public function bandejaLimpieza()
    {
        $pendientes = DB::table('bd_censos_campo as c')
            ->join('bd_activos_maestro as a', 'c.act_id', '=', 'a.act_id')
            ->join('bd_ubicaciones as u', 'c.ubi_id', '=', 'u.ubi_id')
            ->join('bd_estados_fisicos as e', 'c.est_id', '=', 'e.est_id')
            ->join('bd_usuarios as op', 'c.usu_id_operador', '=', 'op.usu_id')
            ->select(
                'c.cen_id',
                'c.cen_fecha',
                'a.act_codigo',
                'a.act_denominacion',
                'e.est_nombre as estado_hallado',
                'u.ubi_nombre as ubicacion_hallada',
                'op.usu_nombres as operador',
                'c.cen_observaciones'
            )
            ->where('c.cen_estado_limpieza', 'PENDIENTE')
            ->orderBy('c.cen_fecha', 'desc')
            ->paginate(15);

        return view('auditoria.limpieza', compact('pendientes'));
    }

    /**
     * Procesa la decisión del Auditor (Aprobar o Rechazar).
     */
    public function conciliarCenso(Request $request)
    {
        $request->validate([
            'cen_id' => 'required|integer',
            'accion' => 'required|in:APROBADO_SIN_CAMBIOS,RECHAZADO',
            'observacion' => 'nullable|string'
        ]);

        $nuevoEstado = $request->accion === 'APROBADO_SIN_CAMBIOS' ? 'CONCILIADO' : 'RECHAZADO';
        
        $idAuditor = Auth::id(); 

        // Regla RF-1: Segregación de Funciones
        $censoOriginal = DB::table('bd_censos_campo')->where('cen_id', $request->cen_id)->first();
        
        if ($censoOriginal->usu_id_operador == $idAuditor) {
            return back()->with('error', 'Violación de control interno: No puedes auditar ni aprobar un registro que tú mismo has levantado en campo.');
        }

        DB::beginTransaction();
        try {
            DB::table('bd_censos_campo')
                ->where('cen_id', $request->cen_id)
                ->update(['cen_estado_limpieza' => $nuevoEstado]);

            DB::table('bd_conciliaciones_limpieza')->insert([
                'cen_id' => $request->cen_id,
                'usu_id_auditor' => $idAuditor,
                'con_accion' => $request->accion,
                'con_observacion_auditor' => $request->observacion,
                'con_fecha_limpieza' => now()
            ]);

            DB::commit();
            return back()->with('success', 'Registro conciliado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al conciliar: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el Padrón Institucional consumiendo la Vista pre-calculada de MariaDB.
     */
    public function padronMaestro(Request $request)
    {
        $query = DB::table('v_bd_tabla_master');

        if ($request->has('buscar')) {
            $query->where('codigo_patrimonial', 'LIKE', '%' . $request->buscar . '%')
                  ->orWhere('denominacion', 'LIKE', '%' . $request->buscar . '%');
        }

        $activos = $query->paginate(20);

        return view('auditoria.padron', compact('activos'));
    }
}