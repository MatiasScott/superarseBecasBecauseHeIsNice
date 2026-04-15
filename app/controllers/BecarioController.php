<?php

// Incluimos los modelos que vamos a usar
require_once BASE_PATH . '/app/models/Becario.php';
require_once BASE_PATH . '/app/models/Certificado.php';
require_once BASE_PATH . '/app/models/Admin.php';

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
        $forgotPasswordUrl = $this->url('/becario/forgot-password');

        require BASE_PATH . '/app/views/home.php';
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
                    $messageType = 'success';
                    $message = 'Solicitud recibida. Un administrador puede resetear tu contraseña desde el panel administrativo.';
                } else {
                    $message = 'No encontramos esa cédula. Verifica el dato e intenta nuevamente.';
                }
            }

            $assetCssPath = $this->assetPath('/assets/css/styles.css');
            $basePath = $this->baseUrl();
            $csrfToken = $this->ensureCsrfToken();
            $homeUrl = $this->url('/');
            require BASE_PATH . '/app/views/forgot_password.php';
            return;
        }

        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();
        $homeUrl = $this->url('/');
        $messageType = '';
        $message = '';
        require BASE_PATH . '/app/views/forgot_password.php';
    }

    public function adminLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->isValidCsrf()) {
                http_response_code(419);
                echo 'La sesión expiró. Recarga la página e intenta nuevamente.';
                return;
            }

            $usuario = trim((string) ($_POST['usuario'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $error = '';

            if ($usuario === '' || $password === '') {
                $error = 'Usuario y contraseña son obligatorios.';
            } else {
                $adminModel = new Admin($this->pdo);
                $admin = $adminModel->buscarPorUsuario($usuario);

                if (!$admin || !$adminModel->verifyPassword($password, (string) ($admin['contrasenia_login'] ?? ''))) {
                    $error = 'Credenciales inválidas.';
                } else {
                    $_SESSION['admin_id'] = (int) $admin['id'];
                    $_SESSION['admin_usuario'] = (string) $admin['usuario'];

                    if ((int) ($admin['primer_inicio'] ?? 1) === 1) {
                        header('Location: ' . $this->url('/admin/change-password'));
                        exit;
                    }

                    header('Location: ' . $this->url('/admin/dashboard'));
                    exit;
                }
            }

            $assetCssPath = $this->assetPath('/assets/css/styles.css');
            $basePath = $this->baseUrl();
            $csrfToken = $this->ensureCsrfToken();
            $errorMessage = $error;
            require BASE_PATH . '/app/views/admin_login.php';
            return;
        }

        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();
        $errorMessage = '';
        require BASE_PATH . '/app/views/admin_login.php';
    }

    public function adminLogout()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Location: ' . $this->url('/admin/login'));
            return;
        }

        if (!$this->isValidCsrf()) {
            http_response_code(419);
            header('Location: ' . $this->url('/admin/login'));
            return;
        }

        unset($_SESSION['admin_id'], $_SESSION['admin_usuario']);
        header('Location: ' . $this->url('/admin/login'));
        exit;
    }

    public function adminChangePassword()
    {
        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        $adminModel = new Admin($this->pdo);
        $admin = $adminModel->buscarPorId((int) ($_SESSION['admin_id'] ?? 0));

        if (!$admin) {
            unset($_SESSION['admin_id'], $_SESSION['admin_usuario']);
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->isValidCsrf()) {
                http_response_code(419);
                echo 'La sesión expiró. Recarga la página e intenta nuevamente.';
                return;
            }

            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
            $errorMessage = '';
            $successMessage = '';

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $errorMessage = 'Completa todos los campos.';
            } elseif (!$adminModel->verifyPassword($currentPassword, (string) ($admin['contrasenia_login'] ?? ''))) {
                $errorMessage = 'La contraseña actual es incorrecta.';
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = 'La confirmación de contraseña no coincide.';
            } else {
                $validation = $this->validatePasswordRequirements($newPassword);
                if (!$validation['valid']) {
                    $errorMessage = $validation['message'];
                } else {
                    $newHash = $adminModel->hashPassword($newPassword);
                    $ok = $adminModel->actualizarContrasenia((int) $admin['id'], $newHash, 0);

                    if ($ok) {
                        $_SESSION['admin_flash'] = [
                            'type' => 'success',
                            'message' => 'Contraseña actualizada correctamente.',
                        ];
                        header('Location: ' . $this->url('/admin/dashboard'));
                        exit;
                    }

                    $errorMessage = 'No se pudo actualizar la contraseña.';
                }
            }

            $assetCssPath = $this->assetPath('/assets/css/styles.css');
            $basePath = $this->baseUrl();
            $csrfToken = $this->ensureCsrfToken();
            $adminUsuario = (string) ($admin['usuario'] ?? '');
            $isFirstLogin = (int) ($admin['primer_inicio'] ?? 0) === 1;
            require BASE_PATH . '/app/views/admin_change_password.php';
            return;
        }

        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();
        $adminUsuario = (string) ($admin['usuario'] ?? '');
        $isFirstLogin = (int) ($admin['primer_inicio'] ?? 0) === 1;
        $errorMessage = '';
        $successMessage = '';
        require BASE_PATH . '/app/views/admin_change_password.php';
    }

    public function adminDashboard()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        $adminModel = new Admin($this->pdo);
        $admin = $adminModel->buscarPorId((int) ($_SESSION['admin_id'] ?? 0));
        if (!$admin) {
            unset($_SESSION['admin_id'], $_SESSION['admin_usuario']);
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        if ((int) ($admin['primer_inicio'] ?? 1) === 1) {
            header('Location: ' . $this->url('/admin/change-password'));
            exit;
        }

        $becarioModel = new Becario($this->pdo);
        $certificadoModel = new Certificado($this->pdo);

        $niveles = self::ALLOWED_LEVELS;
        $resumenEstudiantes = $becarioModel->obtenerResumenDashboard();
        $estadoContrasenias = $becarioModel->obtenerEstadoContrasenias();
        $conteoNiveles = $certificadoModel->obtenerConteoPorNiveles($niveles);
        $resumenCertificados = $certificadoModel->obtenerResumenGeneral();
        $ultimasCargas = $certificadoModel->obtenerUltimasCargas(10);

        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();
        $resetPasswordAction = $this->url('/admin/reset-password');
        $createAdminAction = $this->url('/admin/create-account');
        $homeUrl = $this->url('/');
        $adminChangePasswordUrl = $this->url('/admin/change-password');
        $adminLogoutUrl = $this->url('/admin/logout');
        $adminUsuario = (string) ($admin['usuario'] ?? '');

        require BASE_PATH . '/app/views/admin_dashboard.php';
    }

    public function adminCreateAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        if (!$this->isValidCsrf()) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La sesión expiró. Recarga el panel antes de intentar de nuevo.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $primerInicio = isset($_POST['primer_inicio']) ? 1 : 0;

        if (!preg_match('/^[a-zA-Z0-9._-]{4,60}$/', $usuario)) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'El usuario admin debe tener entre 4 y 60 caracteres y solo usar letras, números, punto, guion o guion bajo.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        if ($password === '' || $confirmPassword === '') {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'Debes ingresar y confirmar la contraseña.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La confirmación de contraseña no coincide.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        $validation = $this->validatePasswordRequirements($password);
        if (!$validation['valid']) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => $validation['message'],
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        $adminModel = new Admin($this->pdo);
        if ($adminModel->buscarPorUsuario($usuario)) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'Ya existe un admin con ese usuario.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        $hash = $adminModel->hashPassword($password);
        $ok = $adminModel->crearAdmin($usuario, $nombre !== '' ? $nombre : null, $hash, $primerInicio);

        $_SESSION['admin_flash'] = [
            'type' => $ok ? 'success' : 'error',
            'message' => $ok
                ? 'Cuenta admin creada correctamente.'
                : 'No se pudo crear la cuenta admin. Verifica que el usuario no exista e intenta nuevamente.',
        ];

        header('Location: ' . $this->url('/admin/dashboard'));
        exit;
    }

    public function adminResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        if (!$this->isValidCsrf()) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La sesión expiró. Recarga el panel antes de intentar de nuevo.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        $cedula = trim((string) ($_POST['cedula'] ?? ''));
        if (!preg_match('/^\d{10}$/', $cedula)) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La cédula debe tener 10 dígitos.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        $becarioModel = new Becario($this->pdo);
        $registro = $becarioModel->buscarPorCedula($cedula);

        if (!$registro) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'No existe un estudiante con esa cédula.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        $ok = $becarioModel->resetearContraseniaLoginPorCedula($cedula);
        $_SESSION['admin_flash'] = [
            'type' => $ok ? 'success' : 'error',
            'message' => $ok
                ? 'Contraseña reseteada correctamente. La nueva contraseña temporal es la cédula del estudiante.'
                : 'No se pudo resetear la contraseña. Intenta nuevamente.',
        ];

        header('Location: ' . $this->url('/admin/dashboard'));
        exit;
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
        $becarioModel = new Becario($this->pdo);
        $becarioData = $becarioModel->buscarPorCedula($cedula);

        // Usuario no encontrado → mostrar vista becario_not_found en modal
        if (!$becarioData) {
            ob_start();
            require BASE_PATH . '/app/views/becario_not_found.php';
            $html = ob_get_clean();
            echo json_encode(['success' => false, 'html' => $html]);
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
            echo json_encode(['success' => false, 'error' => 'Cédula o contraseña incorrecta.']);
            return;
        }

        $_SESSION['authorized_cedula'] = $cedula;
        $csrfToken = $this->ensureCsrfToken();

        // Detectar primer ingreso: contraseña aún es la cédula (sin cambiar)
        if ($becarioModel->verifyPassword($cedula, (string) $becarioData['contrasenia_login'])) {
            $changePasswordUrl = $this->url('/becario/change-password');
            ob_start();
            require BASE_PATH . '/app/views/change_password_modal.php';
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
        require BASE_PATH . '/app/views/becario_form.php';
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

    private function isAdminAuthenticated(): bool
    {
        return isset($_SESSION['admin_id']) && (int) $_SESSION['admin_id'] > 0;
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
                'url' => $ruta !== null ? $this->url('/becario/descargar?ruta=' . rawurlencode($ruta)) : null,
            ];
        }

        require BASE_PATH . '/app/views/registro_exitoso.php';
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

            require BASE_PATH . '/app/views/change_password.php';
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
        require BASE_PATH . '/app/views/becario_form.php';
        $html = ob_get_clean();

        echo json_encode([
            'success' => true,
            'message' => '¡Contraseña actualizada correctamente!',
            'html' => $html,
        ]);
    }
}