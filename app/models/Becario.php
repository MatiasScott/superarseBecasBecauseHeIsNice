<?php

class Becario
{
    // Propiedad para la conexión a la base de datos
    private $pdo;

    // El constructor recibe la conexión PDO
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca un becario en la base de datos por su número de cédula.
     * @param string $cedula La cédula a buscar.
     * @return array|null Un array asociativo con los datos del becario o null si no se encuentra.
     */
    public function buscarPorCedula($cedula)
    {
        try {
            $sql = "SELECT u.*,
                    lw.enlace AS whatsapp_link,
                    lm.enlace AS moodle_link
            FROM usuarios u
            LEFT JOIN links_whatsapp lw ON u.link_whatsapp_id = lw.id
            LEFT JOIN links_moodle lm ON u.link_moodle_id = lm.id
            WHERE u.cedula = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cedula]);
            return $stmt->fetch(); // Retorna los datos como un array asociativo o false si no hay resultados
        } catch (PDOException $e) {
            // Manejo de errores: registra el error pero no lo muestra al usuario
            error_log("Error al buscar becario por cédula: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Hashea una contraseña usando bcrypt.
     * @param string $password La contraseña a hashear.
     * @return string La contraseña hasheada.
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifica si una contraseña coincide con su hash.
     * @param string $password La contraseña a verificar.
     * @param string $hash El hash almacenado.
     * @return bool True si la contraseña es correcta, false si no.
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Actualiza la contraseña de login del becario.
     * @param string $cedula La cédula del becario.
     * @param string $passwordHash La contraseña hasheada.
     * @return bool True si la actualización fue exitosa, false si no.
     */
    public function updatePassword($cedula, $passwordHash)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE usuarios SET contrasenia_login = ? WHERE cedula = ?");
            return $stmt->execute([$passwordHash, $cedula]);
        } catch (PDOException $e) {
            error_log("Error al actualizar contraseña de login: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la ciudad y provincia del becario y obtiene toda la información necesaria.
     * @param string $cedula La cédula del becario.
     * @param string $ciudad La nueva ciudad.
     * @param string $provincia La nueva provincia.
     * @return array|null Un array con todos los datos del becario y los enlaces o null si no se encuentra.
     */
    public function procesarRegistro($cedula, $ciudad, $provincia)
    {
        try {
            // Iniciar una transacción para asegurar que todas las operaciones se completen o ninguna lo haga
            $this->pdo->beginTransaction();

            // Actualiza ciudad y provincia
            $stmt = $this->pdo->prepare("UPDATE usuarios SET ciudad = ?, provincia = ? WHERE cedula = ?");
            $stmt->execute([$ciudad, $provincia, $cedula]);

            // Obtener los links y la info adicional en una sola consulta
            $stmt = $this->pdo->prepare("
            SELECT u.*, lw.enlace as whatsapp_link, lm.enlace as moodle_link
            FROM usuarios u
            LEFT JOIN links_whatsapp lw ON u.link_whatsapp_id = lw.id
            LEFT JOIN links_moodle lm ON u.link_moodle_id = lm.id
            WHERE u.cedula = ?
        ");
            $stmt->execute([$cedula]);
            $usuario = $stmt->fetch();

            $this->pdo->commit(); // Confirmar la transacción

            return $usuario;
        } catch (PDOException $e) {
            $this->pdo->rollBack(); // Revertir los cambios si ocurre un error
            error_log("Error al procesar el registro del becario: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Resetea la contraseña de login a la cédula del estudiante.
     * @param string $cedula
     * @return bool
     */
    public function resetearContraseniaLoginPorCedula($cedula)
    {
        try {
            $hash = $this->hashPassword($cedula);
            $stmt = $this->pdo->prepare("UPDATE usuarios SET contrasenia_login = ? WHERE cedula = ?");
            return $stmt->execute([$hash, $cedula]);
        } catch (PDOException $e) {
            error_log("Error al resetear contraseña de login: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas generales de estudiantes.
     * @return array
     */
    public function obtenerResumenDashboard()
    {
        try {
            $sql = "SELECT
                        COUNT(*) AS total_estudiantes,
                        SUM(CASE WHEN COALESCE(ciudad, '') <> '' AND COALESCE(provincia, '') <> '' THEN 1 ELSE 0 END) AS perfiles_completos,
                        SUM(CASE WHEN COALESCE(ciudad, '') = '' OR COALESCE(provincia, '') = '' THEN 1 ELSE 0 END) AS perfiles_pendientes
                    FROM usuarios";

            $stmt = $this->pdo->query($sql);
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

            return [
                'total_estudiantes' => (int) ($row['total_estudiantes'] ?? 0),
                'perfiles_completos' => (int) ($row['perfiles_completos'] ?? 0),
                'perfiles_pendientes' => (int) ($row['perfiles_pendientes'] ?? 0),
            ];
        } catch (PDOException $e) {
            error_log("Error al obtener resumen de dashboard: " . $e->getMessage());
            return [
                'total_estudiantes' => 0,
                'perfiles_completos' => 0,
                'perfiles_pendientes' => 0,
            ];
        }
    }

    /**
     * Obtiene estado de contraseñas de login.
     * @return array
     */
    public function obtenerEstadoContrasenias()
    {
        try {
            // Evitar password_verify por cada fila: en tablas grandes provoca timeouts.
            // Aquí reportamos contraseñas configuradas vs sin contraseña de login.
            $sql = "SELECT
                        COUNT(*) AS total,
                        SUM(CASE WHEN COALESCE(contrasenia_login, '') = '' THEN 1 ELSE 0 END) AS sin_password
                    FROM usuarios";
            $stmt = $this->pdo->query($sql);
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

            $total = (int) ($row['total'] ?? 0);
            $sinPassword = (int) ($row['sin_password'] ?? 0);
            $configuradas = max(0, $total - $sinPassword);

            return [
                'contrasenias_iniciales' => 0,
                'contrasenias_personalizadas' => $configuradas,
                'sin_password_login' => $sinPassword,
            ];
        } catch (PDOException $e) {
            error_log("Error al obtener estado de contraseñas: " . $e->getMessage());
            return [
                'contrasenias_iniciales' => 0,
                'contrasenias_personalizadas' => 0,
                'sin_password_login' => 0,
            ];
        }
    }
}
