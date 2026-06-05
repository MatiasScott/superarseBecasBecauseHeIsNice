<?php

require_once BASE_PATH . '/app/models/Admin.php';
require_once BASE_PATH . '/app/models/Becario.php';
require_once BASE_PATH . '/app/models/Certificado.php';

class AdminController
{
    private $pdo;
    private const ALLOWED_LEVELS = ['A1', 'A2', 'B1', 'B2'];

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function adminLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->isValidCsrf()) {
                http_response_code(419);
                echo 'La sesion expiro. Recarga la pagina e intenta nuevamente.';
                return;
            }

            $usuario = trim((string) ($_POST['usuario'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $error = '';

            if ($usuario === '' || $password === '') {
                $error = 'Usuario y contrasena son obligatorios.';
            } else {
                $adminModel = new Admin($this->pdo);
                $admin = $adminModel->buscarPorUsuario($usuario);

                if (!$admin || !$adminModel->verifyPassword($password, (string) ($admin['contrasenia_login'] ?? ''))) {
                    $error = 'Credenciales invalidas.';
                } else {
                    session_regenerate_id(true);
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
            require BASE_PATH . '/app/views/admin/admin_login.php';
            return;
        }

        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();
        $errorMessage = '';
        require BASE_PATH . '/app/views/admin/admin_login.php';
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
                echo 'La sesion expiro. Recarga la pagina e intenta nuevamente.';
                return;
            }

            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
            $errorMessage = '';

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $errorMessage = 'Completa todos los campos.';
            } elseif (!$adminModel->verifyPassword($currentPassword, (string) ($admin['contrasenia_login'] ?? ''))) {
                $errorMessage = 'La contrasena actual es incorrecta.';
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = 'La confirmacion de contrasena no coincide.';
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
                            'message' => 'Contrasena actualizada correctamente.',
                        ];
                        header('Location: ' . $this->url('/admin/dashboard'));
                        exit;
                    }

                    $errorMessage = 'No se pudo actualizar la contrasena.';
                }
            }

            $assetCssPath = $this->assetPath('/assets/css/styles.css');
            $basePath = $this->baseUrl();
            $csrfToken = $this->ensureCsrfToken();
            $adminUsuario = (string) ($admin['usuario'] ?? '');
            $isFirstLogin = (int) ($admin['primer_inicio'] ?? 0) === 1;
            require BASE_PATH . '/app/views/admin/admin_change_password.php';
            return;
        }

        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();
        $adminUsuario = (string) ($admin['usuario'] ?? '');
        $isFirstLogin = (int) ($admin['primer_inicio'] ?? 0) === 1;
        $errorMessage = '';
        $successMessage = '';
        require BASE_PATH . '/app/views/admin/admin_change_password.php';
    }

    public function adminDashboard()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo 'Metodo no permitido.';
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

        $resumenModulo = $this->obtenerDatosModuloResumenAdmin();
        $filtrosEstudiantes = $this->obtenerFiltrosSolicitudesDesdeRequest();
        $estudiantesModulo = $this->obtenerDatosModuloEstudiantesAdmin($filtrosEstudiantes);
        $adminsModulo = $this->obtenerDatosModuloCuentasAdmin();
        $certificadosModulo = $this->obtenerDatosModuloCertificadosAdmin();

        $niveles = $certificadosModulo['niveles'];
        $resumenEstudiantes = $resumenModulo['resumenEstudiantes'];
        $estadoContrasenias = $resumenModulo['estadoContrasenias'];
        $conteoNiveles = $certificadosModulo['conteoNiveles'];
        $resumenCertificados = $certificadosModulo['resumenCertificados'];
        $ultimasCargas = $certificadosModulo['ultimasCargas'];

        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        $assetCssPath = $this->assetPath('/assets/css/styles.css');
        $basePath = $this->baseUrl();
        $csrfToken = $this->ensureCsrfToken();
        $resetPasswordAction = $estudiantesModulo['resetPasswordAction'];
        $discardResetRequestAction = $estudiantesModulo['discardResetRequestAction'];
        $solicitudesReset = $estudiantesModulo['solicitudesReset'];
        $totalSolicitudesPendientes = $estudiantesModulo['totalSolicitudesPendientes'];
        $filtroFechaDesde = $estudiantesModulo['filtroFechaDesde'];
        $filtroFechaHasta = $estudiantesModulo['filtroFechaHasta'];
        $filtroSoloPendientes = $estudiantesModulo['filtroSoloPendientes'];
        $createAdminAction = $adminsModulo['createAdminAction'];
        $admins = $adminsModulo['admins'] ?? [];
        $bulkUploadAction = $certificadosModulo['bulkUploadAction'];
        $uploadCertificadoAction = $certificadosModulo['uploadCertificadoAction'];
        $listarCertificadosUrl   = $certificadosModulo['listarCertificadosUrl'];
        $verCertificadoUrl       = $certificadosModulo['verCertificadoUrl'];
        $homeUrl = $this->url('/');
        $adminChangePasswordUrl = $this->url('/admin/change-password');
        $adminLogoutUrl = $this->url('/admin/logout');
        $adminUsuario = (string) ($admin['usuario'] ?? '');

        require BASE_PATH . '/app/views/admin/admin_dashboard.php';
    }

    public function adminCreateAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Metodo no permitido.';
            return;
        }

        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        if (!$this->isValidCsrf()) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La sesion expiro. Recarga el panel antes de intentar de nuevo.',
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
                'message' => 'El usuario admin debe tener entre 4 y 60 caracteres y solo usar letras, numeros, punto, guion o guion bajo.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        if ($password === '' || $confirmPassword === '') {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'Debes ingresar y confirmar la contrasena.',
            ];
            header('Location: ' . $this->url('/admin/dashboard'));
            exit;
        }

        if ($password !== $confirmPassword) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La confirmacion de contrasena no coincide.',
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
            echo 'Metodo no permitido.';
            return;
        }

        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        if (!$this->isValidCsrf()) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La sesion expiro. Recarga el panel antes de intentar de nuevo.',
            ];
            header('Location: ' . $this->url('/admin/dashboard?tab=estudiantes'));
            exit;
        }

        $cedula = trim((string) ($_POST['cedula'] ?? ''));
        $solicitudId = (int) ($_POST['request_id'] ?? 0);
        if (!preg_match('/^\d{10}$/', $cedula)) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La cedula debe tener 10 digitos.',
            ];
            header('Location: ' . $this->url('/admin/dashboard?tab=estudiantes'));
            exit;
        }

        $becarioModel = new Becario($this->pdo);
        $registro = $becarioModel->buscarPorCedula($cedula);

        if (!$registro) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'No existe un estudiante con esa cedula.',
            ];
            header('Location: ' . $this->url('/admin/dashboard?tab=estudiantes'));
            exit;
        }

        $ok = $becarioModel->resetearContraseniaLoginPorCedula($cedula);
        if ($ok && $solicitudId > 0) {
            $becarioModel->marcarSolicitudResetAtendida($solicitudId, (int) ($_SESSION['admin_id'] ?? 0));
        }

        $_SESSION['admin_flash'] = [
            'type' => $ok ? 'success' : 'error',
            'message' => $ok
                ? 'Contrasena reseteada correctamente. La nueva contrasena temporal es la cedula del estudiante.'
                : 'No se pudo resetear la contrasena. Intenta nuevamente.',
        ];

        header('Location: ' . $this->url('/admin/dashboard?tab=estudiantes'));
        exit;
    }

    public function adminDiscardResetRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Metodo no permitido.';
            return;
        }

        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        if (!$this->isValidCsrf()) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'La sesion expiro. Recarga el panel antes de intentar de nuevo.',
            ];
            header('Location: ' . $this->url('/admin/dashboard?tab=estudiantes'));
            exit;
        }

        $solicitudId = (int) ($_POST['request_id'] ?? 0);
        if ($solicitudId <= 0) {
            $_SESSION['admin_flash'] = [
                'type' => 'error',
                'message' => 'Solicitud invalida para descartar.',
            ];
            header('Location: ' . $this->url('/admin/dashboard?tab=estudiantes'));
            exit;
        }

        $becarioModel = new Becario($this->pdo);
        $ok = $becarioModel->marcarSolicitudResetDescartada($solicitudId, (int) ($_SESSION['admin_id'] ?? 0));

        $_SESSION['admin_flash'] = [
            'type' => $ok ? 'success' : 'error',
            'message' => $ok
                ? 'Solicitud descartada correctamente.'
                : 'No se pudo descartar la solicitud. Es posible que ya haya sido atendida.',
        ];

        header('Location: ' . $this->url('/admin/dashboard?tab=estudiantes'));
        exit;
    }

    public function adminBulkUploadCertificados()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        if (!$this->isAdminAuthenticated()) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        if (!$this->isValidCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        header('Content-Type: application/json');

        $nivel = (string) ($_SERVER['HTTP_X_CERT_NIVEL'] ?? ($_POST['nivel'] ?? ''));

        if (!$nivel) {
            echo json_encode(['success' => false, 'error' => 'Nivel no especificado']);
            return;
        }

        if (!in_array($nivel, self::ALLOWED_LEVELS, true)) {
            echo json_encode(['success' => false, 'error' => 'Nivel no válido']);
            return;
        }

        // Cuando el payload supera post_max_size, PHP vacia $_POST/$_FILES silenciosamente.
        if (empty($_FILES['pdfs']) || !is_array($_FILES['pdfs']['name'])) {
            $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
            $postMaxBytes = $this->iniSizeToBytes((string) ini_get('post_max_size'));
            if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
                echo json_encode(['success' => false, 'error' => 'El lote excede el limite del servidor (post_max_size=' . ini_get('post_max_size') . ').']);
                return;
            }

            echo json_encode(['success' => false, 'error' => 'No se recibieron archivos en el lote']);
            return;
        }

        $dirUploads = $this->getUploadsDir();

        if (!is_dir($dirUploads) || !is_writable($dirUploads)) {
            echo json_encode([
                'success' => false,
                'error' => 'La carpeta uploads no existe o no tiene permisos de escritura en el servidor. Ruta detectada: ' . $dirUploads,
            ]);
            return;
        }

        $certificadoModel = new Certificado($this->pdo);
        $cedulas = $this->obtenerCedulasValidas();

        $exitosos = 0;
        $errores  = 0;
        $mensajes = [];

        $totalFiles = count($_FILES['pdfs']['name']);
        for ($i = 0; $i < $totalFiles; $i++) {
            $uploadError  = $_FILES['pdfs']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            $tmpPath      = $_FILES['pdfs']['tmp_name'][$i] ?? '';
            $nombreArchivo = basename((string) ($_FILES['pdfs']['name'][$i] ?? ''));

            if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file($tmpPath)) {
                $errores++;
                $mensajes[] = "❌ {$nombreArchivo}: Error en la subida (código {$uploadError})";
                continue;
            }

            // Validar que sea PDF por MIME
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmpPath);
            if ($mime !== 'application/pdf') {
                $errores++;
                $mensajes[] = "❌ {$nombreArchivo}: No es un archivo PDF válido";
                continue;
            }

            $partes = explode('_', $nombreArchivo);
            if (empty($partes[0])) {
                $errores++;
                $mensajes[] = "❌ {$nombreArchivo}: No se pudo extraer la cédula";
                continue;
            }

            $cedula = $partes[0];

            // Quitar extensión si es el único segmento
            $cedula = preg_replace('/\.pdf$/i', '', $cedula);

            if (!preg_match('/^\d+$/', $cedula) || strlen($cedula) > 10) {
                $errores++;
                $mensajes[] = "❌ {$nombreArchivo}: Cédula inválida ({$cedula})";
                continue;
            }

            if (strlen($cedula) < 10) {
                $cedula = str_pad($cedula, 10, '0', STR_PAD_LEFT);
            }

            if (!in_array($cedula, $cedulas, true)) {
                $errores++;
                $mensajes[] = "⚠️ {$nombreArchivo}: Cédula {$cedula} no encontrada en BD";
                continue;
            }

            $randomSuffix = bin2hex(random_bytes(6));
            $nombreDestino = $cedula . '_' . $nivel . '_' . time() . '_' . $randomSuffix . '.pdf';
            $rutaDestino   = $dirUploads . $nombreDestino;
            $rutaParaDb    = 'uploads/' . $nombreDestino;

            if (!move_uploaded_file($tmpPath, $rutaDestino)) {
                // Fallback: en algunos servidores move_uploaded_file puede fallar por permisos/ruta;
                // intentamos copiar desde tmp para no perder el lote completo.
                $copied = is_readable($tmpPath) ? @copy($tmpPath, $rutaDestino) : false;
                if (!$copied) {
                    $lastError = error_get_last();
                    $detalle = $lastError['message'] ?? 'sin detalle';
                    $errores++;
                    $mensajes[] = "❌ Cédula {$cedula}: No se pudo guardar el archivo ({$detalle})";
                    continue;
                }

                @unlink($tmpPath);
            }

            $ok = $certificadoModel->guardarCertificado($cedula, $nivel, $rutaParaDb);
            if ($ok) {
                $exitosos++;
            } else {
                @unlink($rutaDestino);
                $errores++;
                $mensajes[] = "❌ Cédula {$cedula}: Error al guardar en base de datos";
            }
        }

        echo json_encode([
            'success'  => true,
            'exitosos' => $exitosos,
            'errores'  => $errores,
            'mensajes' => $mensajes,
        ]);
    }

    public function adminListarCertificados()
    {
        header('Content-Type: application/json');

        if (!$this->isAdminAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        $busqueda  = trim((string) ($_GET['q'] ?? ''));
        $pagina    = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina = 15;

        $certificadoModel = new Certificado($this->pdo);
        $resultado = $certificadoModel->buscarCertificados($busqueda, $pagina, $porPagina);

        echo json_encode([
            'success'   => true,
            'total'     => $resultado['total'],
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
            'totalPags' => (int) ceil($resultado['total'] / $porPagina),
            'filas'     => $resultado['filas'],
        ]);
    }

    public function adminVerCertificado()
    {
        if (!$this->isAdminAuthenticated()) {
            http_response_code(401);
            echo 'No autorizado.';
            return;
        }

        $ruta = trim((string) ($_GET['ruta'] ?? ''));
        if ($ruta === '') {
            http_response_code(400);
            echo 'Ruta de archivo inválida.';
            return;
        }

        // Resolver rutas relativas (uploads/...) segun la carpeta fisica del entorno.
        $rutaArchivo = $this->resolveUploadFilePath($ruta);

        $rutaReal = realpath($rutaArchivo);
        if ($rutaReal === false || !is_file($rutaReal)) {
            http_response_code(404);
            echo 'El archivo no existe en el servidor.';
            return;
        }

        $uploadsBase = $this->getExistingUploadsBase();
        $rutaRealNorm = str_replace('\\', '/', (string) $rutaReal);
        $uploadsBaseNorm = $uploadsBase ? (rtrim(str_replace('\\', '/', $uploadsBase), '/') . '/') : '';
        if ($uploadsBaseNorm === '' || strpos($rutaRealNorm, $uploadsBaseNorm) !== 0) {
            http_response_code(403);
            echo 'Ruta de archivo no permitida.';
            return;
        }

        $safeFilename = str_replace(["\r", "\n", '"'], '', basename($rutaReal));

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $safeFilename . '"');
        header('Content-Length: ' . filesize($rutaReal));
        header('X-Content-Type-Options: nosniff');
        readfile($rutaReal);
        exit;
    }

    private function obtenerCedulasValidas(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT cedula FROM usuarios ORDER BY cedula ASC');
            $cedulas = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cedulas[] = (string) ($row['cedula'] ?? '');
            }
            return $cedulas;
        } catch (PDOException $e) {
            error_log('Error al obtener cédulas: ' . $e->getMessage());
            return [];
        }
    }

    public function adminUploadCertificado()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        if (!$this->isAdminAuthenticated()) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            return;
        }

        if (!$this->isValidCsrf()) {
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
            return;
        }

        $cedula = trim((string) ($_POST['cedula'] ?? ''));
        $nivel  = trim((string) ($_POST['nivel'] ?? ''));

        if (!preg_match('/^\d{10}$/', $cedula)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'La cédula debe tener 10 dígitos.']);
            return;
        }

        if (!in_array($nivel, self::ALLOWED_LEVELS, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nivel no válido.']);
            return;
        }

        if (!isset($_FILES['certificado']) || (int) ($_FILES['certificado']['error'] ?? 1) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No se recibió el archivo o hubo un error al subirlo.']);
            return;
        }

        $archivo = $_FILES['certificado'];
        $maxSize = 10 * 1024 * 1024; // 10 MB para admin
        if ((int) $archivo['size'] <= 0 || (int) $archivo['size'] > $maxSize) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El archivo excede el tamaño máximo (10 MB).']);
            return;
        }

        $extension = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $archivo['tmp_name']) : null;
        if ($finfo) finfo_close($finfo);

        if ($extension !== 'pdf' || $mimeType !== 'application/pdf') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Solo se permiten archivos PDF válidos.']);
            return;
        }

        // Verificar que la cédula existe en BD
        $becarioModel = new Becario($this->pdo);
        if (!$becarioModel->buscarPorCedula($cedula)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'No existe un estudiante con esa cédula.']);
            return;
        }

        $dirUploads = $this->getUploadsDir();

        if (!is_dir($dirUploads) || !is_writable($dirUploads)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'La carpeta uploads no existe o no tiene permisos de escritura en el servidor. Ruta detectada: ' . $dirUploads]);
            return;
        }

        $nombreDestino = $cedula . '_' . $nivel . '_' . time() . '.pdf';
        $rutaDestino   = $dirUploads . $nombreDestino;
        $rutaParaDb    = 'uploads/' . $nombreDestino;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo en el servidor.']);
            return;
        }

        $certificadoModel = new Certificado($this->pdo);
        $ok = $certificadoModel->guardarCertificado($cedula, $nivel, $rutaParaDb);

        if (!$ok) {
            @unlink($rutaDestino);
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al guardar en base de datos.']);
            return;
        }

        echo json_encode(['success' => true, 'message' => "Certificado nivel {$nivel} para cédula {$cedula} subido correctamente."]);
    }

    private function getUploadsDir(): string
    {
        foreach ($this->getUploadDirCandidates() as $candidate) {
            $parentDir = dirname(rtrim($candidate, '/\\'));
            if (is_dir($candidate)) {
                return rtrim($candidate, '/\\') . '/';
            }

            if (is_dir($parentDir) && is_writable($parentDir)) {
                @mkdir($candidate, 0755, true);
                if (is_dir($candidate)) {
                    return rtrim($candidate, '/\\') . '/';
                }
            }
        }

        $fallback = $this->getUploadDirCandidates();
        return rtrim($fallback[0], '/\\') . '/';
    }

    private function getExistingUploadsBase(): ?string
    {
        foreach ($this->getUploadDirCandidates() as $candidato) {
            if (is_dir($candidato)) {
                $real = realpath($candidato);
                return $real !== false ? $real : $candidato;
            }
        }

        return null;
    }

    private function resolveUploadFilePath(string $ruta): string
    {
        $ruta = trim($ruta);
        if ($ruta === '') {
            return $ruta;
        }

        if (file_exists($ruta)) {
            return $ruta;
        }

        $rutaNormalizada = ltrim($ruta, '/\\');
        if (preg_match('#^uploads[/\\\\]#', $rutaNormalizada) === 1) {
            $relativa = preg_replace('#^uploads[/\\\\]#', '', $rutaNormalizada);
            foreach ($this->getUploadDirCandidates() as $base) {
                $base = rtrim($base, '/\\') . '/';
                $candidato = $base . $relativa;
                if (file_exists($candidato)) {
                    return $candidato;
                }
            }

            return $this->getUploadsDir() . $relativa;
        }

        $basePathRuta = BASE_PATH . '/' . $rutaNormalizada;
        return file_exists($basePathRuta) ? $basePathRuta : $ruta;
    }

    private function getUploadDirCandidates(): array
    {
        $parentBase = dirname(BASE_PATH);

        return [
            $parentBase . '/public_html/uploads',
            BASE_PATH . '/public_html/uploads',
            BASE_PATH . '/uploads',
        ];
    }

    private function obtenerDatosModuloResumenAdmin(): array
    {
        $becarioModel = new Becario($this->pdo);

        return [
            'resumenEstudiantes' => $becarioModel->obtenerResumenDashboard(),
            'estadoContrasenias' => $becarioModel->obtenerEstadoContrasenias(),
        ];
    }

    private function obtenerDatosModuloEstudiantesAdmin(array $filtros = []): array
    {
        $becarioModel = new Becario($this->pdo);
        $solicitudesReset = $becarioModel->obtenerSolicitudesResetPendientes($filtros, 80);

        return [
            'resetPasswordAction' => $this->url('/admin/reset-password'),
            'discardResetRequestAction' => $this->url('/admin/discard-reset-request'),
            'solicitudesReset' => $solicitudesReset,
            'totalSolicitudesPendientes' => $becarioModel->contarSolicitudesResetPendientes(),
            'filtroFechaDesde' => (string) ($filtros['fecha_desde'] ?? ''),
            'filtroFechaHasta' => (string) ($filtros['fecha_hasta'] ?? ''),
            'filtroSoloPendientes' => isset($filtros['solo_pendientes']) && (int) $filtros['solo_pendientes'] === 1,
        ];
    }

    private function obtenerFiltrosSolicitudesDesdeRequest(): array
    {
        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));
        // Solo activar el filtro si el formulario fue enviado (tiene 'tab' en GET) y el checkbox estaba marcado.
        // Si es carga inicial (sin parámetros), mostrar todas las solicitudes.
        $formEnviado = isset($_GET['tab']);
        $soloPendientes = $formEnviado && isset($_GET['solo_pendientes']) && (int) $_GET['solo_pendientes'] === 1;

        if ($fechaDesde !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $fechaDesde = '';
        }

        if ($fechaHasta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $fechaHasta = '';
        }

        return [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'solo_pendientes' => $soloPendientes ? 1 : 0,
        ];
    }

    private function obtenerDatosModuloCuentasAdmin(): array
    {
        $adminModel = new Admin($this->pdo);
        $admins = $adminModel->obtenerTodos();

        return [
            'createAdminAction' => $this->url('/admin/create-account'),
            'admins' => $admins,
        ];
    }

    private function obtenerDatosModuloCertificadosAdmin(): array
    {
        $certificadoModel = new Certificado($this->pdo);
        $niveles = self::ALLOWED_LEVELS;

        return [
            'niveles' => $niveles,
            'conteoNiveles' => $certificadoModel->obtenerConteoPorNiveles($niveles),
            'resumenCertificados' => $certificadoModel->obtenerResumenGeneral(),
            'ultimasCargas' => $certificadoModel->obtenerUltimasCargas(10),
            'bulkUploadAction'       => $this->url('/admin/bulk-upload-certificados'),
            'uploadCertificadoAction' => $this->url('/admin/upload-certificado'),
            'listarCertificadosUrl'   => $this->url('/admin/listar-certificados'),
            'verCertificadoUrl'       => $this->url('/admin/ver-certificado'),
        ];
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

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $num = (int) $value;

        if ($unit === 'g') {
            return $num * 1024 * 1024 * 1024;
        }
        if ($unit === 'm') {
            return $num * 1024 * 1024;
        }
        if ($unit === 'k') {
            return $num * 1024;
        }

        return $num;
    }

    private function isAdminAuthenticated(): bool
    {
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        if ($adminId <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM admins WHERE id = ? AND activo = 1 LIMIT 1');
            $stmt->execute([$adminId]);
            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error al validar sesion admin: ' . $e->getMessage());
            return false;
        }
    }

    private function validatePasswordRequirements($password)
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'La contrasena debe tener al menos 8 caracteres.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contrasena debe contener al menos una letra minuscula.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contrasena debe contener al menos una letra mayuscula.';
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            $errors[] = 'La contrasena debe contener al menos un caracter especial.';
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'message' => implode(' ', $errors),
            ];
        }

        return ['valid' => true, 'message' => ''];
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
}
