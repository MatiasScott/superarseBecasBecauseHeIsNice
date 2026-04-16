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
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 300;

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
        $forgotPasswordUrl = $this->url('/becario/forgot-password');

        require BASE_PATH . '/app/views/student/home.php';
    }

    public function panel()
    {
        $cedula = (string) ($_SESSION['authorized_cedula'] ?? '');

        if ($cedula === '' || !preg_match('/^\d{10}$/', $cedula)) {
            header('Location: ' . $this->url('/'));
            exit;
        }

        $becarioModel = new Becario($this->pdo);
        $registro = $becarioModel->buscarPorCedula($cedula);

        if (!$registro) {
            session_unset();
            header('Location: ' . $this->url('/'));
            exit;
        }

        $this->renderPanel($registro);
    }

    public function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Location: ' . $this->url('/'));
            return;
        }

        if (!$this->isValidCsrf()) {
            http_response_code(419);
            header('Location: ' . $this->url('/'));
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
        header('Location: ' . $this->url('/'));
        exit;
    }

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->isValidCsrf()) {
                http_response_code(419);
                echo 'La sesión expiró. Recarga la página e intenta nuevamente.';
                return;
            }

            $cedula = trim((string) ($_POST['cedula'] ?? ''));
            $messageType = 'error';
            $message = 'Debes ingresar una cédula válida de 10 dígitos.';

            if (preg_match('/^\d{10}$/', $cedula)) {
                $becarioModel = new Becario($this->pdo);
                $becarioData = $becarioModel->buscarPorCedula($cedula);

                if ($becarioData) {
                    $ok = $becarioModel->crearSolicitudResetContrasenia($cedula);
                    if ($ok) {
                        $messageType = 'success';
                        $message = 'Solicitud recibida. Un administrador puede resetear tu contrasena desde el panel administrativo.';
                    } else {
                        $message = 'No pudimos registrar la solicitud en este momento. Intenta nuevamente.';
                    }
                } else {
                    $message = 'No encontramos esa cédula. Verifica el dato e intenta nuevamente.';
                }
            }

            $assetCssPath = $this->assetPath('/assets/css/styles.css');
            $basePath = $this->baseUrl();
            $csrfToken = $this->ensureCsrfToken();
            $homeUrl = $this->url('/');
            require BASE_PATH . '/app/views/student/forgot_password.php';
            return;
        }

        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();
        $homeUrl = $this->url('/');
        $messageType = '';
        $message = '';
        require BASE_PATH . '/app/views/student/forgot_password.php';
    }

    public function login()
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

        if (!isset($_POST['cedula']) || !preg_match('/^\d{10}$/', trim($_POST['cedula']))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Por favor, ingresa una cédula válida de 10 dígitos.']);
            return;
        }

        $password = (string) ($_POST['password'] ?? '');
        if ($password === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ingresa tu contraseña para continuar.']);
            return;
        }

        $cedula = trim($_POST['cedula']);

        if ($this->isLoginRateLimited($cedula)) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Demasiados intentos. Intenta nuevamente en unos minutos.',
            ]);
            return;
        }

        $becarioModel = new Becario($this->pdo);
        $becarioData = $becarioModel->buscarPorCedula($cedula);

        // Respuesta genérica para evitar enumeración de cédulas.
        if (!$becarioData) {
            $this->registerLoginFailure($cedula);
            echo json_encode(['success' => false, 'error' => 'Cédula o contraseña incorrecta.']);
            return;
        }

        // Inicializar contrasenia_login si es NULL (primer acceso al sistema)
        if (!$this->ensureLoginPassword($becarioModel, $becarioData, $cedula)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'No fue posible preparar tu acceso. Intenta nuevamente.']);
            return;
        }

        // Verificar contraseña
        if (!$becarioModel->verifyPassword($password, (string) $becarioData['contrasenia_login'])) {
            $this->registerLoginFailure($cedula);
            echo json_encode(['success' => false, 'error' => 'Cédula o contraseña incorrecta.']);
            return;
        }

        $this->clearLoginFailures($cedula);
        session_regenerate_id(true);
        $_SESSION['authorized_cedula'] = $cedula;
        $csrfToken = $this->ensureCsrfToken();

        // Detectar primer ingreso: contraseña aún es la cédula (sin cambiar)
        if ($becarioModel->verifyPassword($cedula, (string) $becarioData['contrasenia_login'])) {
            $changePasswordUrl = $this->url('/becario/change-password');
            ob_start();
            require BASE_PATH . '/app/views/student/change_password_modal.php';
            $html = ob_get_clean();
            echo json_encode(['success' => true, 'require_change' => true, 'html' => $html]);
            return;
        }

        // Flujo normal: si ya completó ciudad/provincia → ir al panel
        if (!empty($becarioData['ciudad']) && !empty($becarioData['provincia'])) {
            echo json_encode(['success' => true, 'redirect' => $this->url('/becario/panel')]);
            return;
        }

        // Flujo normal: mostrar formulario de datos
        $data = ['becario' => $becarioData, 'provincias' => $this->getProvincias()];
        $formAction = $this->url('/becario/procesar');

        ob_start();
        require BASE_PATH . '/app/views/student/becario_form.php';
        $html = ob_get_clean();

        echo json_encode(['success' => true, 'html' => $html]);
    }

    // Compatibilidad con la ruta anterior.
    public function buscar()
    {
        $this->login();
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

        $this->renderPanel($registro);
    }

    // Método para descargar archivos de forma segura
    public function descargar() {
        $cedula = trim((string) ($_GET['cedula'] ?? ''));
        $nivel  = trim((string) ($_GET['nivel'] ?? ''));

        if ($cedula === '' || $nivel === '') {
            http_response_code(400);
            echo "Error: Parámetros incompletos.";
            exit;
        }

        if (!preg_match('/^\d{10}$/', $cedula)) {
            http_response_code(400);
            echo "Error: Cédula inválida.";
            exit;
        }

        if (!$this->isAuthorizedCedula($cedula)) {
            http_response_code(403);
            echo "Error: No autorizado.";
            exit;
        }

        // Buscar la ruta real del certificado en la base de datos
        $certificadoModel = new Certificado($this->pdo);
        $certificados = $certificadoModel->getCertificadosByCedula($cedula);

        if (!isset($certificados[$nivel])) {
            http_response_code(404);
            echo "Error: Certificado no encontrado.";
            exit;
        }

        $rutaArchivo = $certificados[$nivel];

        // Si es ruta relativa (uploads/...) resolver desde BASE_PATH
        if (!file_exists($rutaArchivo)) {
            $rutaArchivo = BASE_PATH . '/' . ltrim($rutaArchivo, '/\\');
        }

        $rutaReal = realpath($rutaArchivo);
        if ($rutaReal === false || !is_file($rutaReal)) {
            http_response_code(404);
            echo "Error: El archivo no existe en el servidor.";
            exit;
        }

        $uploadsBase = realpath(BASE_PATH . '/uploads');
        $rutaRealNorm = str_replace('\\', '/', (string) $rutaReal);
        $uploadsBaseNorm = $uploadsBase ? (rtrim(str_replace('\\', '/', $uploadsBase), '/') . '/') : '';
        if ($uploadsBaseNorm === '' || strpos($rutaRealNorm, $uploadsBaseNorm) !== 0) {
            http_response_code(403);
            echo "Error: Ruta de archivo no permitida.";
            exit;
        }

        $safeFilename = str_replace(["\r", "\n", '"'], '', basename($rutaReal));

        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($rutaReal));

        readfile($rutaReal);
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

        if (isset($_POST['_csrf'])) {
            return (string) $_POST['_csrf'];
        }

        return (string) ($_POST['csrf_token'] ?? '');
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

    private function getClientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        return $ip !== '' ? $ip : 'unknown';
    }

    private function getLoginAttemptKey(string $cedula): string
    {
        return $this->getClientIp() . '|' . $cedula;
    }

    private function isLoginRateLimited(string $cedula): bool
    {
        $key = $this->getLoginAttemptKey($cedula);
        $attempts = $_SESSION['login_attempts'][$key] ?? [];

        if (!is_array($attempts)) {
            return false;
        }

        $ahora = time();
        $vigentes = array_values(array_filter($attempts, static function ($ts) use ($ahora) {
            return is_int($ts) && ($ahora - $ts) <= self::LOGIN_WINDOW_SECONDS;
        }));

        $_SESSION['login_attempts'][$key] = $vigentes;
        return count($vigentes) >= self::LOGIN_MAX_ATTEMPTS;
    }

    private function registerLoginFailure(string $cedula): void
    {
        $key = $this->getLoginAttemptKey($cedula);
        $attempts = $_SESSION['login_attempts'][$key] ?? [];
        if (!is_array($attempts)) {
            $attempts = [];
        }

        $attempts[] = time();
        $_SESSION['login_attempts'][$key] = $attempts;
    }

    private function clearLoginFailures(string $cedula): void
    {
        $key = $this->getLoginAttemptKey($cedula);
        if (isset($_SESSION['login_attempts'][$key])) {
            unset($_SESSION['login_attempts'][$key]);
        }
    }

    private function ensureLoginPassword(Becario $becarioModel, array &$becarioData, string $cedula): bool
    {
        if (!empty($becarioData['contrasenia_login'])) {
            return true;
        }

        $initialPassword = $becarioModel->hashPassword($cedula);
        if (!$becarioModel->updatePassword($cedula, $initialPassword)) {
            return false;
        }

        $becarioData['contrasenia_login'] = $initialPassword;
        return true;
    }

    private function renderPanel(array $registro): void
    {
        $cedula = (string) ($registro['cedula'] ?? '');
        if ($cedula === '' || !$this->isAuthorizedCedula($cedula)) {
            http_response_code(403);
            echo 'No tienes autorización para acceder a este registro.';
            return;
        }

        $certificadoModel = new Certificado($this->pdo);
        $certificados_encontrados = $certificadoModel->getCertificadosByCedula($cedula);
        $niveles_deseados = self::ALLOWED_LEVELS;
        $data = $registro;
        // Los links ahora vienen del JOIN en buscarPorCedula(), pero agregamos valores por defecto si no existen
        $data['moodle_link'] = !empty($data['moodle_link']) ? $data['moodle_link'] : '#';
        $data['whatsapp_link'] = !empty($data['whatsapp_link']) ? $data['whatsapp_link'] : '#';
        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $videoBecaInglesUrl = $this->assetPath('/assets/videos/becaIngles.mp4');
        $videoMoodleUrl = $this->assetPath('/assets/videos/tutorialMoodle.mp4');
        $videoZoomUrl = $this->assetPath('/assets/videos/tutorialZoom.mp4');
        $logoutUrl = $this->url('/becario/logout');
        $csrfToken = $this->ensureCsrfToken();

        $certificadosVista = [];
        foreach ($niveles_deseados as $nivel) {
            $ruta = $certificados_encontrados[$nivel] ?? null;
            $certificadosVista[] = [
                'nivel' => $nivel,
                'disponible' => $ruta !== null,
                'url' => $ruta !== null ? $this->url('/becario/descargar?cedula=' . rawurlencode($cedula) . '&nivel=' . rawurlencode($nivel)) : null,
            ];
        }

        require BASE_PATH . '/app/views/student/registro_exitoso.php';
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

    /**
     * Valida que una contraseña cumpla con los requisitos de seguridad.
     * Requisitos:
     * - Mínimo 8 caracteres
     * - Al menos una letra minúscula
     * - Al menos una letra mayúscula
     * - Al menos un carácter especial
     * 
     * @param string $password La contraseña a validar.
     * @return array Un array con 'valid' => bool y 'message' => string.
     */
    private function validatePasswordRequirements($password)
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra minúscula.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra mayúscula.';
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial.';
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'message' => implode(' ', $errors)
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Procesa el cambio de contraseña del becario.
     */
    public function changePassword()
    {
        // GET: Renderizar formulario de cambio de contraseña
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (!isset($_SESSION['authorized_cedula'])) {
                header('Location: ' . $this->baseUrl() . '/');
                exit;
            }

            $cedula = (string) $_SESSION['authorized_cedula'];
            $csrfToken = $this->ensureCsrfToken();
            $assetCssPath = $this->assetPath('/assets/css/styles.css');
            $basePath = $this->baseUrl();
            $changePasswordUrl = $this->url('/becario/change-password');
            $registroUrl = $this->url('/becario/panel');

            require BASE_PATH . '/app/views/student/change_password.php';
            return;
        }

        // POST: Procesar cambio de contraseña
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método de solicitud no válido.']);
            return;
        }

        header('Content-Type: application/json');

        if (!$this->isValidCsrf()) {
            http_response_code(419);
            echo json_encode(['success' => false, 'error' => 'La sesión expiró. Recarga la página e intenta nuevamente.']);
            return;
        }

        $cedula = trim((string) ($_POST['cedula'] ?? ''));
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');

        if (!$cedula || $currentPassword === '' || $newPassword === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Faltan datos requeridos.']);
            return;
        }

        if (!preg_match('/^\d{10}$/', $cedula)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'La cédula proporcionada no es válida.']);
            return;
        }

        if (!$this->isAuthorizedCedula($cedula)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tienes autorización para cambiar la contraseña.']);
            return;
        }

        $becarioModel = new Becario($this->pdo);
        $becarioData = $becarioModel->buscarPorCedula($cedula);

        if (!$becarioData) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado.']);
            return;
        }

        // Verificar contraseña actual
        if (!$becarioModel->verifyPassword($currentPassword, (string) $becarioData['contrasenia_login'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'La contraseña actual es incorrecta.']);
            return;
        }

        $validation = $this->validatePasswordRequirements($newPassword);
        if (!$validation['valid']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $validation['message']]);
            return;
        }

        $passwordHash = $becarioModel->hashPassword($newPassword);

        if (!$becarioModel->updatePassword($cedula, $passwordHash)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la contraseña.']);
            return;
        }

        // Determinar el paso siguiente después del cambio exitoso
        if (!empty($becarioData['ciudad']) && !empty($becarioData['provincia'])) {
            echo json_encode([
                'success' => true,
                'message' => '¡Contraseña actualizada correctamente!',
                'redirect' => $this->url('/becario/panel'),
            ]);
            return;
        }

        // Aún falta completar ciudad/provincia → mostrar becario_form
        $csrfToken = $this->ensureCsrfToken();
        $data = ['becario' => $becarioData, 'provincias' => $this->getProvincias()];
        $formAction = $this->url('/becario/procesar');

        ob_start();
        require BASE_PATH . '/app/views/student/becario_form.php';
        $html = ob_get_clean();

        echo json_encode([
            'success' => true,
            'message' => '¡Contraseña actualizada correctamente!',
            'html' => $html,
        ]);
    }
}