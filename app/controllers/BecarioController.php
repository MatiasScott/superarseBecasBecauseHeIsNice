<?php

// Incluimos los modelos que vamos a usar
require_once BASE_PATH . '/app/models/Becario.php';
require_once BASE_PATH . '/app/models/Certificado.php';

class BecarioController
{
    private $pdo;
    private const MAX_UPLOAD_SIZE = 5242880; // 5 MB
    private const ALLOWED_UPLOAD_MIME = 'application/pdf';
    private const ALLOWED_LEVELS = ['A1', 'A2', 'B1', 'B2'];

    public function __construct()
    {
        // Usar la conexión PDO del ámbito global definida en index.php
        global $pdo;
        $this->pdo = $pdo;
    }

    public function home()
    {
        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $homeJsPath = $this->assetPath('/assets/js/home.js');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();

        require BASE_PATH . '/app/views/home.php';
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

        if (!$this->isValidCsrf()) {
            http_response_code(419);
            echo json_encode(['success' => false, 'error' => 'La sesión expiró. Recarga la página e intenta nuevamente.']);
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
            $_SESSION['authorized_cedula'] = $cedula;

            $data = [
                'becario' => $becarioData,
                'provincias' => $this->getProvincias(),
            ];

            $csrfToken = $this->ensureCsrfToken();
            $formAction = $this->url('/becario/procesar');

            ob_start();
            require BASE_PATH . '/app/views/becario_form.php';
            $html = ob_get_clean();

            echo json_encode([
                'success' => true,
                'data' => $data,
                'html' => $html,
            ]);
        } else {
            http_response_code(404); // No encontrado
            ob_start();
            require BASE_PATH . '/app/views/becario_not_found.php';
            $html = ob_get_clean();

            echo json_encode([
                'success' => false,
                'error' => 'El becario con la cédula proporcionada no fue encontrado.',
                'html' => $html,
            ]);
        }
    }

    // Método para procesar la actualización de datos del becario
    public function procesar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método de solicitud no válido.';
            return;
        }

        if (!$this->isValidCsrf()) {
            http_response_code(419);
            echo 'La sesión expiró. Recarga la página e intenta nuevamente.';
            return;
        }

        $cedula = trim((string) ($_POST['cedula'] ?? ''));
        $ciudad = trim((string) ($_POST['ciudad'] ?? ''));
        $provincia = trim((string) ($_POST['provincia'] ?? ''));

        if (!preg_match('/^\d{10}$/', $cedula)) {
            http_response_code(400);
            echo 'La cédula proporcionada no es válida.';
            return;
        }

        if (!$cedula || !$ciudad || !$provincia) {
            http_response_code(400);
            echo 'Faltan datos requeridos.';
            return;
        }

        if (!$this->isAuthorizedCedula($cedula)) {
            http_response_code(403);
            echo 'No tienes autorización para modificar este registro.';
            return;
        }

        if (!in_array($provincia, $this->getProvincias(), true)) {
            http_response_code(400);
            echo 'La provincia seleccionada no es válida.';
            return;
        }

        if (mb_strlen($ciudad) < 2 || mb_strlen($ciudad) > 80) {
            http_response_code(400);
            echo 'La ciudad debe tener entre 2 y 80 caracteres.';
            return;
        }

        $becarioModel = new Becario($this->pdo);
        $registro = $becarioModel->procesarRegistro($cedula, $ciudad, $provincia);

        if (!$registro) {
            http_response_code(500);
            echo 'Error al actualizar los datos en la base de datos.';
            return;
        }

        $certificadoModel = new Certificado($this->pdo);
        $certificados_encontrados = $certificadoModel->getCertificadosByCedula($cedula);
        $niveles_deseados = self::ALLOWED_LEVELS;
        $data = $registro;
        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $videoBecaInglesUrl = $this->assetPath('/assets/videos/becaIngles.mp4');
        $videoMoodleUrl = $this->assetPath('/assets/videos/tutorialMoodle.mp4');
        $videoZoomUrl = $this->assetPath('/assets/videos/tutorialZoom.mp4');

        $certificadosVista = [];
        foreach ($niveles_deseados as $nivel) {
            $ruta = $certificados_encontrados[$nivel] ?? null;
            $certificadosVista[] = [
                'nivel' => $nivel,
                'disponible' => $ruta !== null,
                'url' => $ruta !== null ? $this->url('/becario/descargar?ruta=' . rawurlencode($ruta)) : null,
            ];
        }

        require BASE_PATH . '/app/views/registro_exitoso.php';
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

        if (!$this->isValidCsrf()) {
            http_response_code(419);
            echo json_encode(['success' => false, 'error' => 'La sesión expiró. Recarga la página e intenta nuevamente.']);
            return;
        }

        $cedula = trim((string) ($_POST['cedula'] ?? ''));
        $nivel = trim((string) ($_POST['nivel'] ?? ''));

        if (!$cedula || !$nivel || !isset($_FILES['certificado']) || $_FILES['certificado']['error'] != 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Faltan datos o el archivo no se subió correctamente.']);
            return;
        }

        if (!preg_match('/^\d{10}$/', $cedula)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'La cédula proporcionada no es válida.']);
            return;
        }

        if (!$this->isAuthorizedCedula($cedula)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes autorización para subir archivos para este registro.']);
            return;
        }

        if (!in_array($nivel, self::ALLOWED_LEVELS, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nivel no válido.']);
            return;
        }

        $archivo = $_FILES['certificado'];
        if (!isset($archivo['size']) || (int) $archivo['size'] <= 0 || (int) $archivo['size'] > self::MAX_UPLOAD_SIZE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El archivo excede el tamaño máximo permitido (5 MB).']);
            return;
        }

        $directorio_subidas = BASE_PATH . '/uploads/';
        
        // Crear el directorio si no existe
        if (!is_dir($directorio_subidas)) {
            mkdir($directorio_subidas, 0755, true);
        }

        $archivo_temporal = $archivo['tmp_name'];
        $nombre_original = basename((string) $archivo['name']);
        $extension = strtolower((string) pathinfo($nombre_original, PATHINFO_EXTENSION));

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $archivo_temporal) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($extension !== 'pdf' || $mimeType !== self::ALLOWED_UPLOAD_MIME) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Solo se permiten archivos PDF válidos.']);
            return;
        }

        $nombre_unico = "{$cedula}_{$nivel}_" . time() . ".{$extension}";
        $ruta_destino = $directorio_subidas . $nombre_unico;
        
        // Ruta que se guardará en la base de datos (relativa a la raíz del proyecto)
        $ruta_para_db = 'uploads/' . $nombre_unico;

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
        
        $ruta_relativa = ltrim((string) $_GET['ruta'], '/\\');
        $directorio_subidas = realpath(BASE_PATH . '/uploads');
        $ruta_absoluta = realpath(BASE_PATH . '/' . $ruta_relativa);

        if (
            $ruta_relativa === '' ||
            strpos($ruta_relativa, 'uploads/') !== 0 ||
            $directorio_subidas === false ||
            $ruta_absoluta === false ||
            strpos($ruta_absoluta, $directorio_subidas) !== 0 ||
            !is_file($ruta_absoluta)
        ) {
            http_response_code(404); // Not Found
            echo "Error: El archivo no existe o la ruta es inválida.";
            exit;
        }

        $nombreArchivo = basename($ruta_absoluta);
        if (!preg_match('/^(\d{10})_[A-Z0-9]+_\d+\.pdf$/i', $nombreArchivo, $coincidencias)) {
            http_response_code(403);
            echo "Error: No autorizado.";
            exit;
        }

        $cedulaArchivo = $coincidencias[1] ?? '';
        if (!$this->isAuthorizedCedula($cedulaArchivo)) {
            http_response_code(403);
            echo "Error: No autorizado.";
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

    private function ensureCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    private function getRequestCsrfToken(): string
    {
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        return (string) ($_POST['_csrf'] ?? '');
    }

    private function isValidCsrf(): bool
    {
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
        $requestToken = $this->getRequestCsrfToken();

        return $sessionToken !== '' && $requestToken !== '' && hash_equals($sessionToken, $requestToken);
    }

    private function isAuthorizedCedula(string $cedula): bool
    {
        return isset($_SESSION['authorized_cedula']) && hash_equals((string) $_SESSION['authorized_cedula'], $cedula);
    }

    private function url(string $path): string
    {
        return $this->baseUrl() . $path;
    }

    private function assetPath(string $path): string
    {
        return $this->baseUrl() . $path;
    }

    private function baseUrl(): string
    {
        return defined('BASE_URL') ? BASE_URL : '';
    }

    private function getProvincias(): array
    {
        return [
            "Azuay", "Bolívar", "Cañar", "Carchi", "Chimborazo", "Cotopaxi",
            "El Oro", "Esmeraldas", "Guayas", "Imbabura", "Loja", "Los Ríos",
            "Manabí", "Morona Santiago", "Napo", "Orellana", "Pastaza",
            "Pichincha", "Santa Elena", "Santo Domingo de los Tsáchilas",
            "Sucumbíos", "Tungurahua", "Zamora Chinchipe"
        ];
    }
}