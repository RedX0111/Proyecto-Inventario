<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE VIEW \"v_bd_tabla_master\" AS SELECT a.act_id AS id,
    a.act_orden AS n_orden,
    a.act_codigo AS codigo_patrimonial,
    a.act_denominacion AS denominacion,
    ma.mar_nombre AS marca,
    mo.mod_nombre AS modelo,
    c.col_nombre AS color,
    sl.cla_descripcion AS tipo,
    a.act_serie AS serie,
    a.act_dimensiones AS dimensiones,
    a.act_es_temporal AS es_temporal,
    p.per_anio AS anio_auditoria,
    cc.cdk_etiqueta_estado AS alerta_movimiento,
    cc.cdk_estado_limpieza AS constatado_actual,
    ef.est_nombre AS estado,
    u.ubi_nombre AS ubicacion
   FROM (((((((((bd_activos_maestro a
     LEFT JOIN mod_modelos mo ON ((a.mod_id = mo.mod_id)))
     LEFT JOIN mar_marcas ma ON ((mo.mar_id = ma.mar_id)))
     LEFT JOIN col_colores c ON ((a.col_id = c.col_id)))
     LEFT JOIN sla_clasificadores sl ON ((a.cla_id = sl.cla_id)))
     LEFT JOIN bd_censo_campo cc ON ((cc.act_id = a.act_id)))
     LEFT JOIN bd_estados_fisicos ef ON ((cc.est_id = ef.est_id)))
     LEFT JOIN cen_censos ce ON ((cc.cer_id = ce.cer_id)))
     LEFT JOIN bd_periodos_fiscales p ON ((ce.per_id = p.per_id)))
     LEFT JOIN bd_ubicaciones u ON ((cc.ubi_id = u.ubi_id)));");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS \"v_bd_tabla_master\"");
    }
};
