<?php

// Incluimos los modelos que vamos a usar
require_once BASE_PATH . '/app/models/Becario.php';
require_once BASE_PATH . '/app/models/Certificado.php';

class BecarioController
{
    private $pdo;

    public function __construct()
    {
        // Usar la conexión PDO del ámbito global definida en index.php
        global $pdo;
        $this->pdo = $pdo;
    }

    // Método principal que maneja la búsqueda de un becario por cédula
    public function buscar()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Método no permitido
            echo json_encode(['success' => false, 'error' => 'Método de solicitud no válido.']);
            return;
        }

        if (!isset($_POST['cedula']) || !preg_match('/^\d{10}$/', trim($_POST['cedula']))) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'error' => 'Por favor, ingresa una cédula válida de 10 dígitos.']);
            return;
        }

        $cedula = trim($_POST['cedula']);
        $becarioModel = new Becario($this->pdo);
        $becarioData = $becarioModel->buscarPorCedula($cedula);

        if ($becarioData) {
            $data = [
                'becario' => $becarioData,
                'provincias' => [
                    "Azuay", "Bolívar", "Cañar", "Carchi", "Chimborazo", "Cotopaxi",
                    "El Oro", "Esmeraldas", "Guayas", "Imbabura", "Loja", "Los Ríos",
                    "Manabí", "Morona Santiago", "Napo", "Orellana", "Pastaza",
                    "Pichincha", "Santa Elena", "Santo Domingo de los Tsáchilas",
                    "Sucumbíos", "Tungurahua", "Zamora Chinchipe"
                ]
            ];
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            http_response_code(404); // No encontrado
            echo json_encode(['success' => false, 'error' => 'El becario con la cédula proporcionada no fue encontrado.']);
        }
    }

    // Método para procesar la actualización de datos del becario
    public function procesar()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método de solicitud no válido.']);
            return;
        }

        $cedula = $_POST['cedula'] ?? '';
        $ciudad = $_POST['ciudad'] ?? '';
        $provincia = $_POST['provincia'] ?? '';

        if (!$cedula || !$ciudad || !$provincia) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos.']);
            return;
        }

        $becarioModel = new Becario($this->pdo);
        $updateResult = $becarioModel->actualizarDatosBecario($cedula, $ciudad, $provincia);

        if ($updateResult) {
            echo json_encode(['success' => true, 'message' => 'Los datos se han actualizado con éxito.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al actualizar los datos en la base de datos.']);
        }
    }

    // Método para procesar la subida de un certificado
    public function procesarSubida()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método de solicitud no válido.']);
            return;
        }

        $cedula = $_POST['cedula'] ?? null;
        $nivel = $_POST['nivel'] ?? null;

        if (!$cedula || !$nivel || !isset($_FILES['certificado']) || $_FILES['certificado']['error'] != 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Faltan datos o el archivo no se subió correctamente.']);
            return;
        }

        $directorio_subidas = BASE_PATH . '/app/uploads/';
        
        // Crear el directorio si no existe
        if (!is_dir($directorio_subidas)) {
            mkdir($directorio_subidas, 0755, true);
        }

        $archivo_temporal = $_FILES['certificado']['tmp_name'];
        $nombre_original = basename($_FILES['certificado']['name']);
        $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
        $nombre_unico = "{$cedula}_{$nivel}_" . time() . ".{$extension}";
        $ruta_destino = $directorio_subidas . $nombre_unico;
        
        // Ruta que se guardará en la base de datos (relativa a la raíz del proyecto)
        $ruta_para_db = 'ISuperarse/app/uploads/' . $nombre_unico;

        if (move_uploaded_file($archivo_temporal, $ruta_destino)) {
            $certificadoModel = new Certificado($this->pdo);
            $guardado_exitoso = $certificadoModel->guardarCertificado($cedula, $nivel, $ruta_para_db);

            if ($guardado_exitoso) {
                echo json_encode(['success' => true, 'message' => '¡El certificado se ha subido y guardado con éxito!']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Error al guardar el certificado en la base de datos.']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al mover el archivo subido.']);
        }
    }

    // Este método ya no es necesario aquí, ya que el JS maneja la vista.
    // Lo he eliminado para simplificar el flujo.
    // private function view($viewName, $data = []) { ... }

    // Método para descargar archivos de forma segura
    public function descargar() {
        if (!isset($_GET['ruta'])) {
            http_response_code(400); // Bad Request
            echo "Error: La ruta del archivo no está especificada.";
            exit;
        }
        
        $ruta_relativa = $_GET['ruta'];
        
        // Validación de la ruta para evitar ataques de directorio transversal
        $ruta_absoluta = BASE_PATH . '/' . str_replace('ISuperarse/', '', $ruta_relativa);
        
        if (strpos($ruta_relativa, '..') !== false || !file_exists($ruta_absoluta)) {
            http_response_code(404); // Not Found
            echo "Error: El archivo no existe o la ruta es inválida.";
            exit;
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($ruta_absoluta) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($ruta_absoluta));
        
        readfile($ruta_absoluta);
        exit;
    }
}