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
}
