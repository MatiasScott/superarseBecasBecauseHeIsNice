ALTER TABLE solicitudes_reset_contrasenia
    MODIFY COLUMN estado ENUM('pendiente', 'atendida', 'descartada') NOT NULL DEFAULT 'pendiente';
