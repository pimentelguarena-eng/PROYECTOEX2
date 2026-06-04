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
        DB::unprepared("
            -- ============================================================================
            -- SCRIPT DE BASE DE DATOS - SISTEMA DE GESTIÓN CUP (UAGRM)
            -- MOTOR: PostgreSQL
            -- ============================================================================

            -- ----------------------------------------------------------------------------
            -- 1. PAQUETE: GESTIÓN DE USUARIOS Y SEGURIDAD
            -- ----------------------------------------------------------------------------

            CREATE TABLE roles (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(50) NOT NULL UNIQUE,
                descripcion TEXT
            );

            CREATE TABLE permisos (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE,
                descripcion TEXT
            );

            CREATE TABLE rol_permiso (
                rol_id INT REFERENCES roles(id) ON DELETE CASCADE,
                permiso_id INT REFERENCES permisos(id) ON DELETE CASCADE,
                PRIMARY KEY (rol_id, permiso_id)
            );

            CREATE TABLE usuarios (
                id BIGSERIAL PRIMARY KEY,
                codigo_registro VARCHAR(20) UNIQUE NOT NULL,
                ci VARCHAR(15) UNIQUE NOT NULL,
                nombre_completo VARCHAR(150) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                rol_id INT NOT NULL REFERENCES roles(id),
                estado BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE bitacoras (
                id BIGSERIAL PRIMARY KEY,
                usuario_id BIGINT REFERENCES usuarios(id) ON DELETE SET NULL,
                accion VARCHAR(255) NOT NULL,
                modulo VARCHAR(100) NOT NULL,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- ----------------------------------------------------------------------------
            -- 2. PAQUETE: REGISTROS Y ADMISIÓN DE POSTULANTES
            -- ----------------------------------------------------------------------------

            CREATE TABLE carreras (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE
            );

            CREATE TABLE estudiantes (
                usuario_id BIGINT PRIMARY KEY REFERENCES usuarios(id) ON DELETE CASCADE,
                carrera_opcion_1 INT NOT NULL REFERENCES carreras(id),
                carrera_opcion_2 INT NOT NULL REFERENCES carreras(id),
                turno_preferido VARCHAR(15) NOT NULL CHECK (turno_preferido IN ('Mañana', 'Tarde', 'Noche')),
                nro_intentos INT DEFAULT 1,
                estado_cup VARCHAR(20) DEFAULT 'Postulante' CHECK (estado_cup IN ('Postulante', 'Inscrito', 'Aprobado', 'Reprobado'))
            );

            CREATE TABLE docentes (
                usuario_id BIGINT PRIMARY KEY REFERENCES usuarios(id) ON DELETE CASCADE,
                especialidad VARCHAR(100)
            );

            CREATE TABLE pagos (
                id BIGSERIAL PRIMARY KEY,
                estudiante_id BIGINT NOT NULL REFERENCES estudiantes(usuario_id) ON DELETE CASCADE,
                monto NUMERIC(8,2) NOT NULL DEFAULT 700.00 CHECK (monto = 700.00),
                nro_factura VARCHAR(50) UNIQUE,
                estado_pago VARCHAR(15) DEFAULT 'Pendiente' CHECK (estado_pago IN ('Pendiente', 'Pagado')),
                fecha_pago TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            -- ----------------------------------------------------------------------------
            -- 3. PAQUETE: GESTIÓN DE GRUPOS Y PLANIFICACIÓN HORARIA
            -- ----------------------------------------------------------------------------

            CREATE TABLE materias (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(50) NOT NULL UNIQUE
            );

            CREATE TABLE grupos (
                id BIGSERIAL PRIMARY KEY,
                sigla VARCHAR(10) NOT NULL,
                materia_id INT NOT NULL REFERENCES materias(id) ON DELETE RESTRICT,
                docente_id BIGINT REFERENCES docentes(usuario_id) ON DELETE SET NULL,
                turno VARCHAR(15) NOT NULL CHECK (turno IN ('Mañana', 'Tarde', 'Noche')),
                cupo_maximo INT DEFAULT 70 CHECK (cupo_maximo <= 70),
                UNIQUE (sigla, materia_id)
            );

            CREATE TABLE grupo_estudiante (
                grupo_id BIGINT REFERENCES grupos(id) ON DELETE CASCADE,
                estudiante_id BIGINT REFERENCES estudiantes(usuario_id) ON DELETE CASCADE,
                PRIMARY KEY (grupo_id, estudiante_id)
            );

            -- ----------------------------------------------------------------------------
            -- 4. PAQUETE: GESTIÓN ACADÉMICA
            -- ----------------------------------------------------------------------------

            CREATE TABLE asistencias (
                id BIGSERIAL PRIMARY KEY,
                estudiante_id BIGINT NOT NULL REFERENCES estudiantes(usuario_id) ON DELETE CASCADE,
                grupo_id BIGINT NOT NULL REFERENCES grupos(id) ON DELETE CASCADE,
                fecha DATE NOT NULL DEFAULT CURRENT_DATE,
                estado VARCHAR(10) NOT NULL CHECK (estado IN ('Presente', 'Falta')),
                UNIQUE (estudiante_id, grupo_id, fecha)
            );

            CREATE TABLE notas (
                id BIGSERIAL PRIMARY KEY,
                estudiante_id BIGINT NOT NULL REFERENCES estudiantes(usuario_id) ON DELETE CASCADE,
                materia_id INT NOT NULL REFERENCES materias(id) ON DELETE RESTRICT,
                nota_parcial_1 NUMERIC(5,2) DEFAULT 0.00 CHECK (nota_parcial_1 >= 0 AND nota_parcial_1 <= 100),
                nota_parcial_2 NUMERIC(5,2) DEFAULT 0.00 CHECK (nota_parcial_2 >= 0 AND nota_parcial_2 <= 100),
                nota_examen_final NUMERIC(5,2) DEFAULT 0.00 CHECK (nota_examen_final >= 0 AND nota_examen_final <= 100),
                nota_final_materia NUMERIC(5,2) DEFAULT 0.00 CHECK (nota_final_materia >= 0 AND nota_final_materia <= 100),
                UNIQUE (estudiante_id, materia_id)
            );

            -- ----------------------------------------------------------------------------
            -- 5. ÍNDICES DE OPTIMIZACIÓN
            -- ----------------------------------------------------------------------------

            CREATE INDEX idx_bitacora_fecha ON bitacoras(created_at);
            CREATE INDEX idx_bitacora_modulo ON bitacoras(modulo);
            CREATE INDEX idx_pagos_control ON pagos(estudiante_id, estado_pago);
            CREATE INDEX idx_notas_rendimiento ON notas(materia_id, nota_final_materia);
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("
            DROP TABLE IF EXISTS asistencias CASCADE;
            DROP TABLE IF EXISTS notas CASCADE;
            DROP TABLE IF EXISTS grupo_estudiante CASCADE;
            DROP TABLE IF EXISTS grupos CASCADE;
            DROP TABLE IF EXISTS pagos CASCADE;
            DROP TABLE IF EXISTS docentes CASCADE;
            DROP TABLE IF EXISTS estudiantes CASCADE;
            DROP TABLE IF EXISTS bitacoras CASCADE;
            DROP TABLE IF EXISTS usuarios CASCADE;
            DROP TABLE IF EXISTS rol_permiso CASCADE;
            DROP TABLE IF EXISTS materias CASCADE;
            DROP TABLE IF EXISTS carreras CASCADE;
            DROP TABLE IF EXISTS permisos CASCADE;
            DROP TABLE IF EXISTS roles CASCADE;
        ");
    }
};
