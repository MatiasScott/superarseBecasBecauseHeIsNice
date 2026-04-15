CREATE TABLE IF NOT EXISTS solicitudes_reset_contrasenia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(10) NOT NULL,
    estado ENUM('pendiente', 'atendida') NOT NULL DEFAULT 'pendiente',
    solicitado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atendido_en DATETIME DEFAULT NULL,
    atendido_por_admin_id INT DEFAULT NULL,
    INDEX idx_solicitudes_estado_fecha (estado, solicitado_en),
    INDEX idx_solicitudes_cedula (cedula),
    CONSTRAINT fk_solicitudes_admin
        FOREIGN KEY (atendido_por_admin_id) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
