<?php

class Certificado
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCertificadosByCedula($cedula)
    {
        try {
            $sql = "SELECT nivel, ruta_archivo FROM certificados WHERE cedula = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cedula]);

            $certificados = [];
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $certificados[$fila['nivel']] = $fila['ruta_archivo'];
            }
            return $certificados;
        } catch (PDOException $e) {
            error_log("Error al obtener certificados: " . $e->getMessage());
            return [];
        }
    }

    public function guardarCertificado($cedula, $nivel, $ruta_archivo)
    {
        try {
            $sql = "INSERT INTO certificados (cedula, nivel, ruta_archivo, fecha_subida) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$cedula, $nivel, $ruta_archivo]);
        } catch (PDOException $e) {
            error_log("Error al guardar el certificado: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerConteoPorNiveles(array $niveles)
    {
        $resultado = [];
        foreach ($niveles as $nivel) {
            $resultado[$nivel] = 0;
        }

        try {
            $stmt = $this->pdo->query("SELECT nivel, COUNT(*) AS total FROM certificados GROUP BY nivel");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $nivel = (string) ($row['nivel'] ?? '');
                if (array_key_exists($nivel, $resultado)) {
                    $resultado[$nivel] = (int) ($row['total'] ?? 0);
                }
            }

            return $resultado;
        } catch (PDOException $e) {
            error_log("Error al obtener conteo por niveles: " . $e->getMessage());
            return $resultado;
        }
    }

    public function obtenerResumenGeneral()
    {
        try {
            $sql = "SELECT
                        COUNT(*) AS total_certificados,
                        COUNT(DISTINCT cedula) AS estudiantes_con_certificados,
                        MAX(fecha_subida) AS ultima_carga
                    FROM certificados";

            $stmt = $this->pdo->query($sql);
            $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

            return [
                'total_certificados' => (int) ($row['total_certificados'] ?? 0),
                'estudiantes_con_certificados' => (int) ($row['estudiantes_con_certificados'] ?? 0),
                'ultima_carga' => (string) ($row['ultima_carga'] ?? ''),
            ];
        } catch (PDOException $e) {
            error_log("Error al obtener resumen general de certificados: " . $e->getMessage());
            return [
                'total_certificados' => 0,
                'estudiantes_con_certificados' => 0,
                'ultima_carga' => '',
            ];
        }
    }

    public function obtenerUltimasCargas($limit = 8)
    {
        try {
            $limit = max(1, min(50, (int) $limit));
            $stmt = $this->pdo->prepare("SELECT cedula, nivel, ruta_archivo, fecha_subida FROM certificados ORDER BY fecha_subida DESC LIMIT ?");
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error al obtener últimas cargas de certificados: " . $e->getMessage());
            return [];
        }
    }
}
