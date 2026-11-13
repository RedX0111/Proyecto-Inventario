<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Crear tablas catálogo base (Nivel 1)
        DB::statement("
            CREATE TABLE bd_periodos_fiscales (
                per_id SERIAL PRIMARY KEY,
                per_anio INT NOT NULL UNIQUE,
                per_fecha_inicio DATE NULL,
                per_fecha_cierre DATE NULL,
                per_cerrado SMALLINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        DB::statement("
            CREATE TABLE col_colores (
                col_id SERIAL PRIMARY KEY,
                col_nombre VARCHAR(100) NOT NULL UNIQUE
            );
        ");

        DB::statement("
            CREATE TABLE sla_clasificadores (
                cla_id SERIAL PRIMARY KEY,
                cla_descripcion VARCHAR(255) NOT NULL UNIQUE
            );
        ");

        DB::statement("
            CREATE TABLE mar_marcas (
                mar_id SERIAL PRIMARY KEY,
                mar_nombre VARCHAR(100) NOT NULL UNIQUE
            );
        ");

        DB::statement("
            CREATE TABLE bd_estados_fisicos (
                est_id SERIAL PRIMARY KEY,
                est_nombre VARCHAR(100) NOT NULL UNIQUE
            );
        ");

        DB::statement("
            CREATE TABLE und_undes (
                und_id SERIAL PRIMARY KEY,
                und_nombre VARCHAR(255) NOT NULL
            );
        ");

        DB::statement("
            CREATE TABLE bd_roles (
                rol_id SERIAL PRIMARY KEY,
                rol_nombre VARCHAR(50) NOT NULL UNIQUE
            );
        ");

        // 2. Tablas catálogo con dependencias (Nivel 2)
        DB::statement("
            CREATE TABLE mod_modelos (
                mod_id SERIAL PRIMARY KEY,
                mod_nombre VARCHAR(100) NOT NULL,
                mar_id INT REFERENCES mar_marcas(mar_id) ON DELETE CASCADE
            );
        ");

        DB::statement("
            CREATE TABLE bd_ubicaciones (
                ubi_id SERIAL PRIMARY KEY,
                ubi_codigo VARCHAR(50) NULL UNIQUE,
                ubi_nombre VARCHAR(255) NOT NULL UNIQUE
            );
        ");

        // 3. Usuarios
        DB::statement("
            CREATE TABLE bd_usuarios (
                usu_id SERIAL PRIMARY KEY,
                usu_dni VARCHAR(20) NOT NULL UNIQUE,
                usu_nombres VARCHAR(100) NOT NULL,
                usu_apellidos VARCHAR(100) NOT NULL,
                usu_correo VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                rol_id INT NOT NULL REFERENCES bd_roles(rol_id),
                usu_activo SMALLINT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 4. Padrón Maestro
        DB::statement("
            CREATE TABLE bd_activos_maestro (
                act_id BIGSERIAL PRIMARY KEY,
                act_orden INT NULL,
                act_codigo VARCHAR(50) NOT NULL UNIQUE,
                act_denominacion VARCHAR(255) NOT NULL,
                mod_id INT REFERENCES mod_modelos(mod_id),
                col_id INT REFERENCES col_colores(col_id),
                cla_id INT REFERENCES sla_clasificadores(cla_id),
                act_serie VARCHAR(100) NULL,
                act_dimensiones VARCHAR(100) NULL,
                act_es_temporal SMALLINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 5. Transaccionales y trazabilidad
        DB::statement("
            CREATE TABLE cen_censos (
                cer_id BIGSERIAL PRIMARY KEY,
                per_id INT NOT NULL REFERENCES bd_periodos_fiscales(per_id),
                cer_fecha DATE NOT NULL,
                cer_observaciones TEXT NULL
            );
        ");

        DB::statement("
            CREATE TABLE bd_censo_campo (
                cdc_id BIGSERIAL PRIMARY KEY,
                cer_id BIGINT NOT NULL REFERENCES cen_censos(cer_id),
                act_id BIGINT NOT NULL REFERENCES bd_activos_maestro(act_id),
                est_id INT REFERENCES bd_estados_fisicos(est_id),
                ubi_id INT REFERENCES bd_ubicaciones(ubi_id),
                cdk_estado_limpieza VARCHAR(50) NULL,
                cdk_etiqueta_estado VARCHAR(50) NULL,
                UNIQUE (cer_id, act_id)
            );
        ");

        DB::statement("
            CREATE TABLE aud_bitacora_trazabilidad (
                aud_id BIGSERIAL PRIMARY KEY,
                act_id BIGINT NOT NULL REFERENCES bd_activos_maestro(act_id),
                usu_id INT NOT NULL REFERENCES bd_usuarios(usu_id),
                aud_accion VARCHAR(100) NOT NULL,
                aud_detalle TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 6. Staging
        DB::statement("
            CREATE TABLE bd_staging_censo (
                stg_id BIGSERIAL PRIMARY KEY,
                stg_orden INT NULL,
                codigo_patrimonial VARCHAR(50) NULL,
                denominacion VARCHAR(255) NULL,
                marca VARCHAR(100) NULL,
                modelo VARCHAR(100) NULL,
                tipo VARCHAR(100) NULL,
                color VARCHAR(50) NULL,
                serie VARCHAR(100) NULL,
                dimensiones VARCHAR(100) NULL,
                fecha_censo DATE NULL,
                anio_auditoria INT NULL,
                estado VARCHAR(50) NULL,
                constatado_actual VARCHAR(50) NULL,
                alerta_movimiento VARCHAR(50) NULL,
                ubicacion VARCHAR(255) NULL,
                observaciones TEXT NULL,
                dni_auditor VARCHAR(20) NULL,
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        // 7. Vista del Dashboard
        DB::statement("
            CREATE OR REPLACE VIEW v_bd_tabla_master AS
            SELECT 
                a.act_id AS id,
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
            FROM bd_activos_maestro a
            LEFT JOIN mod_modelos mo ON a.mod_id = mo.mod_id
            LEFT JOIN mar_marcas ma ON mo.mar_id = ma.mar_id
            LEFT JOIN col_colores c ON a.col_id = c.col_id
            LEFT JOIN sla_clasificadores sl ON a.cla_id = sl.cla_id
            LEFT JOIN bd_censo_campo cc ON cc.act_id = a.act_id
            LEFT JOIN bd_estados_fisicos ef ON cc.est_id = ef.est_id
            LEFT JOIN cen_censos ce ON cc.cer_id = ce.cer_id
            LEFT JOIN bd_periodos_fiscales p ON ce.per_id = p.per_id
            LEFT JOIN bd_ubicaciones u ON cc.ubi_id = u.ubi_id;
        ");
    }

    public function down()
    {
        DB::statement("DROP VIEW IF EXISTS v_bd_tabla_master;");
        DB::statement("DROP TABLE IF EXISTS bd_staging_censo CASCADE;");
        DB::statement("DROP TABLE IF EXISTS aud_bitacora_trazabilidad CASCADE;");
        DB::statement("DROP TABLE IF EXISTS bd_censo_campo CASCADE;");
        DB::statement("DROP TABLE IF EXISTS cen_censos CASCADE;");
        DB::statement("DROP TABLE IF EXISTS bd_activos_maestro CASCADE;");
        DB::statement("DROP TABLE IF EXISTS bd_usuarios CASCADE;");
        DB::statement("DROP TABLE IF EXISTS bd_ubicaciones CASCADE;");
        DB::statement("DROP TABLE IF EXISTS mod_modelos CASCADE;");
        DB::statement("DROP TABLE IF EXISTS bd_roles CASCADE;");
        DB::statement("DROP TABLE IF EXISTS und_undes CASCADE;");
        DB::statement("DROP TABLE IF EXISTS bd_estados_fisicos CASCADE;");
        DB::statement("DROP TABLE IF EXISTS mar_marcas CASCADE;");
        DB::statement("DROP TABLE IF EXISTS sla_clasificadores CASCADE;");
        DB::statement("DROP TABLE IF EXISTS col_colores CASCADE;");
        DB::statement("DROP TABLE IF EXISTS bd_periodos_fiscales CASCADE;");
    }
};