-- Tabla de administradores para acceso privado del dashboard
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(60) NOT NULL UNIQUE,
    nombre VARCHAR(120) DEFAULT NULL,
    contrasenia_login VARCHAR(255) NOT NULL,
    primer_inicio TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario inicial de ejemplo
-- Usuario: admin
-- Contrasena temporal: Admin123!
-- Debe cambiarla en el primer inicio de sesion.
INSERT INTO admins (usuario, nombre, contrasenia_login, primer_inicio, activo)
VALUES ('admin', 'Administrador principal', '$2y$10$kr6ArNojPZGShlxPjigS.Ou0wq1nGhvau91kc.cEwoiJ/RsJ3p2dm', 1, 1)
ON DUPLICATE KEY UPDATE usuario = VALUES(usuario);
