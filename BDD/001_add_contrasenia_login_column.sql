-- ====================================================================================
-- MIGRACIÓN: Agregar columna contrasenia_login para autenticación del sistema
-- ====================================================================================
-- Esta migración agrega una nueva columna separada de 'contrasenia' (Moodle)
-- para almacenar las contraseñas de login del sistema con bcrypt
--
-- Fecha: 14 de abril de 2026
-- Base de datos: becasupe_sistema_usuarios
-- ====================================================================================

USE `becasupe_sistema_usuarios`;

-- Agregar columna contrasenia_login si no existe
ALTER TABLE `usuarios`
ADD COLUMN `contrasenia_login` VARCHAR(255) NULL DEFAULT NULL
AFTER `contrasenia`;

-- Crear índice para búsquedas rápidas (opcional pero recomendado)
-- ALTER TABLE `usuarios` ADD KEY `idx_contrasenia_login` (`contrasenia_login`);

-- Notas:
-- 1. La columna `contrasenia` sigue siendo para Moodle (no modificar)
-- 2. La nueva columna `contrasenia_login` es para autenticación del sistema
-- 3. Se inicializará automáticamente con la cédula hasheada en la primera búsqueda
-- 4. VARCHAR(255) es suficiente para bcrypt hashes (60 caracteres típicos)
-- ====================================================================================
