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

    public function buscarCertificados(string $busqueda, int $pagina, int $porPagina): array
    {
        $offset = ($pagina - 1) * $porPagina;
        $like   = '%' . $busqueda . '%';
        $porPagina = max(1, (int) $porPagina);
        $offset = max(0, (int) $offset);

        try {
            $where = $busqueda !== ''
                ? "WHERE c.cedula LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?"
                : "";

            $params = $busqueda !== '' ? [$like, $like, $like] : [];

            $sqlTotal = "SELECT COUNT(*) FROM certificados c
                         LEFT JOIN usuarios u ON BINARY c.cedula = BINARY u.cedula
                         $where";
            $stmtT = $this->pdo->prepare($sqlTotal);
            $stmtT->execute($params);
            $total = (int) $stmtT->fetchColumn();

            $sqlData = "SELECT c.cedula, c.nivel, c.ruta_archivo, c.fecha_subida,
                               u.nombres, u.apellidos
                        FROM certificados c
                       LEFT JOIN usuarios u ON BINARY c.cedula = BINARY u.cedula
                        $where
                        ORDER BY c.fecha_subida DESC
                        LIMIT {$porPagina} OFFSET {$offset}";

            $stmtD = $this->pdo->prepare($sqlData);
            // Bind solo para filtros de texto (LIMIT/OFFSET ya saneados como enteros)
            $i = 1;
            foreach ($params as $p) {
                $stmtD->bindValue($i++, $p, PDO::PARAM_STR);
            }
            $stmtD->execute();

            return [
                'total'  => $total,
                'filas'  => $stmtD->fetchAll(PDO::FETCH_ASSOC) ?: [],
            ];
        } catch (PDOException $e) {
            error_log("Error al buscar certificados: " . $e->getMessage());

            // Fallback: si falla el JOIN/columnas de usuarios, listar desde certificados.
            try {
                $whereSimple = $busqueda !== '' ? "WHERE cedula LIKE ?" : "";
                $paramsSimple = $busqueda !== '' ? [$like] : [];

                $stmtTs = $this->pdo->prepare("SELECT COUNT(*) FROM certificados {$whereSimple}");
                $stmtTs->execute($paramsSimple);
                $totalSimple = (int) $stmtTs->fetchColumn();

                $stmtDs = $this->pdo->prepare("SELECT cedula, nivel, ruta_archivo, fecha_subida
                                               FROM certificados
                                               {$whereSimple}
                                               ORDER BY fecha_subida DESC
                                               LIMIT {$porPagina} OFFSET {$offset}");
                if ($busqueda !== '') {
                    $stmtDs->bindValue(1, $like, PDO::PARAM_STR);
                }
                $stmtDs->execute();

                return [
                    'total' => $totalSimple,
                    'filas' => $stmtDs->fetchAll(PDO::FETCH_ASSOC) ?: [],
                ];
            } catch (PDOException $e2) {
                error_log("Error en fallback de certificados: " . $e2->getMessage());
                return ['total' => 0, 'filas' => []];
            }
        }
    }
}
