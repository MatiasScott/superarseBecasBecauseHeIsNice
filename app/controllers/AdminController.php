<?php

require_once BASE_PATH . '/app/models/Admin.php';
require_once BASE_PATH . '/app/models/Becario.php';
require_once BASE_PATH . '/app/models/Certificado.php';

$vendorAutoloadPath = BASE_PATH . '/vendor/autoload.php';
if (is_file($vendorAutoloadPath)) {
    require_once $vendorAutoloadPath;
}

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
        $totalSolicitudesFiltradas = $estudiantesModulo['totalSolicitudesFiltradas'];
        $paginaSolicitudes = $estudiantesModulo['paginaSolicitudes'];
        $totalPaginasSolicitudes = $estudiantesModulo['totalPaginasSolicitudes'];
        $desdeSolicitudes = $estudiantesModulo['desdeSolicitudes'];
        $hastaSolicitudes = $estudiantesModulo['hastaSolicitudes'];
        $prevSolicitudesUrl = $estudiantesModulo['prevSolicitudesUrl'];
        $nextSolicitudesUrl = $estudiantesModulo['nextSolicitudesUrl'];
        $filtroBusquedaSolicitudes = $estudiantesModulo['filtroBusquedaSolicitudes'];
        $filtroFechaDesde = $estudiantesModulo['filtroFechaDesde'];
        $filtroFechaHasta = $estudiantesModulo['filtroFechaHasta'];
        $filtroSoloPendientes = $estudiantesModulo['filtroSoloPendientes'];
        $listadoEstudiantesAcceso = $estudiantesModulo['listadoEstudiantesAcceso'];
        $totalEstudiantesAcceso = $estudiantesModulo['totalEstudiantesAcceso'];
        $paginaEstudiantesAcceso = $estudiantesModulo['paginaEstudiantesAcceso'];
        $totalPaginasEstudiantesAcceso = $estudiantesModulo['totalPaginasEstudiantesAcceso'];
        $desdeEstudiantesAcceso = $estudiantesModulo['desdeEstudiantesAcceso'];
        $hastaEstudiantesAcceso = $estudiantesModulo['hastaEstudiantesAcceso'];
        $prevEstudiantesAccesoUrl = $estudiantesModulo['prevEstudiantesAccesoUrl'];
        $nextEstudiantesAccesoUrl = $estudiantesModulo['nextEstudiantesAccesoUrl'];
        $exportEstudiantesAccesoExcelUrl = $estudiantesModulo['exportEstudiantesAccesoExcelUrl'];
        $exportEstudiantesAccesoPdfUrl = $estudiantesModulo['exportEstudiantesAccesoPdfUrl'];
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

    public function adminExportEstudiantesUltimoAccesoExcel()
    {
        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        @set_time_limit(0);

        $becarioModel = new Becario($this->pdo);
        $total = $becarioModel->contarTotalEstudiantes();

        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet') || !class_exists('PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx')) {
            $this->exportarEstudiantesAccesoCsvFallback($becarioModel, $total);
            return;
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ultimo acceso');
        $sheet->fromArray(['Cedula', 'Estudiante', 'Nivel', 'Ultimo acceso'], null, 'A1');

        $filaExcel = 2;
        $offset = 0;
        $lote = 1000;

        while ($offset < $total) {
            $filas = $becarioModel->obtenerListadoEstudiantesUltimoAccesoPorLote($offset, $lote);
            if (empty($filas)) {
                break;
            }

            foreach ($filas as $fila) {
                $nombres = trim((string) ($fila['nombres'] ?? ''));
                $apellidos = trim((string) ($fila['apellidos'] ?? ''));
                $nombreCompleto = trim($nombres . ' ' . $apellidos);
                $nivel = trim((string) ($fila['nivel'] ?? ''));
                $ultimoAcceso = $this->formatearFechaIngresoConDia((string) ($fila['fecha_ingreso_landing'] ?? ''));

                $sheet->setCellValueExplicit('A' . $filaExcel, (string) ($fila['cedula'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('B' . $filaExcel, $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre');
                $sheet->setCellValue('C' . $filaExcel, $nivel !== '' ? $nivel : '-');
                $sheet->setCellValue('D' . $filaExcel, $ultimoAcceso !== '' ? $ultimoAcceso : '-');
                $filaExcel++;
            }

            $offset += count($filas);
        }

        $filename = 'reporte_estudiantes_ultimo_acceso_todos_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }

    public function adminExportEstudiantesUltimoAccesoPdf()
    {
        if (!$this->isAdminAuthenticated()) {
            header('Location: ' . $this->url('/admin/login'));
            exit;
        }

        @set_time_limit(0);

        $becarioModel = new Becario($this->pdo);
        $total = $becarioModel->contarTotalEstudiantes();

        $lineas = [];
        $lineas[] = 'REPORTE DE ESTUDIANTES - ULTIMO ACCESO';
        $lineas[] = 'Total de estudiantes: ' . $total;
        $lineas[] = 'Generado: ' . date('d-m-Y H:i:s');
        $lineas[] = str_repeat('-', 120);
        $lineas[] = str_pad('Cedula', 14) . str_pad('Estudiante', 52) . str_pad('Nivel', 10) . 'Ultimo acceso';
        $lineas[] = str_repeat('-', 120);

        $offset = 0;
        $lote = 1000;
        while ($offset < $total) {
            $filas = $becarioModel->obtenerListadoEstudiantesUltimoAccesoPorLote($offset, $lote);
            if (empty($filas)) {
                break;
            }

            foreach ($filas as $fila) {
                $nombres = trim((string) ($fila['nombres'] ?? ''));
                $apellidos = trim((string) ($fila['apellidos'] ?? ''));
                $nombreCompleto = trim($nombres . ' ' . $apellidos);
                $nivel = trim((string) ($fila['nivel'] ?? ''));
                $ultimoAcceso = $this->formatearFechaIngresoConDia((string) ($fila['fecha_ingreso_landing'] ?? ''));

                $lineas[] = str_pad(substr((string) ($fila['cedula'] ?? ''), 0, 13), 14)
                    . str_pad(substr($nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre', 0, 50), 52)
                    . str_pad(substr($nivel !== '' ? $nivel : '-', 0, 8), 10)
                    . ($ultimoAcceso !== '' ? $ultimoAcceso : '-');
            }

            $offset += count($filas);
        }

        $pdfBytes = $this->crearPdfTextoSimple($lineas);
        $filename = 'reporte_estudiantes_ultimo_acceso_todos_' . date('Ymd_His') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBytes));
        header('X-Content-Type-Options: nosniff');
        echo $pdfBytes;
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
        $paginaSolicitudes = max(1, (int) ($_GET['pagina_solicitudes'] ?? 1));
        $porPaginaSolicitudes = 20;
        $resultadoSolicitudes = $becarioModel->obtenerSolicitudesResetPendientesPaginado($filtros, $paginaSolicitudes, $porPaginaSolicitudes);
        $solicitudesReset = $resultadoSolicitudes['filas'] ?? [];
        $paginaSolicitudes = (int) ($resultadoSolicitudes['pagina'] ?? 1);
        $totalSolicitudesFiltradas = (int) ($resultadoSolicitudes['total'] ?? 0);
        $totalPaginasSolicitudes = (int) ($resultadoSolicitudes['totalPaginas'] ?? 1);

        $paginaAccesos = max(1, (int) ($_GET['pagina_accesos'] ?? 1));
        $porPaginaAccesos = 50;
        $listadoAccesosPaginado = $becarioModel->obtenerListadoEstudiantesUltimoAccesoPaginado($paginaAccesos, $porPaginaAccesos);

        $paginaAccesos = (int) ($listadoAccesosPaginado['pagina'] ?? 1);
        $totalAccesos = (int) ($listadoAccesosPaginado['total'] ?? 0);
        $totalPaginasAccesos = (int) ($listadoAccesosPaginado['totalPaginas'] ?? 1);
        $listadoEstudiantesAcceso = $listadoAccesosPaginado['filas'] ?? [];

        $paramsBase = ['tab' => 'estudiantes'];
        if (!empty($filtros['fecha_desde'])) {
            $paramsBase['fecha_desde'] = (string) $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $paramsBase['fecha_hasta'] = (string) $filtros['fecha_hasta'];
        }
        if (isset($filtros['solo_pendientes']) && (int) $filtros['solo_pendientes'] === 1) {
            $paramsBase['solo_pendientes'] = '1';
        }
        if (!empty($filtros['q'])) {
            $paramsBase['q'] = (string) $filtros['q'];
        }

        $desdeSolicitudes = $totalSolicitudesFiltradas > 0 ? (($paginaSolicitudes - 1) * $porPaginaSolicitudes + 1) : 0;
        $hastaSolicitudes = $totalSolicitudesFiltradas > 0 ? min($paginaSolicitudes * $porPaginaSolicitudes, $totalSolicitudesFiltradas) : 0;
        $prevSolicitudesUrl = '';
        $nextSolicitudesUrl = '';

        if ($paginaSolicitudes > 1) {
            $paramsPrevSolicitudes = $paramsBase;
            $paramsPrevSolicitudes['pagina_solicitudes'] = (string) ($paginaSolicitudes - 1);
            $paramsPrevSolicitudes['pagina_accesos'] = (string) $paginaAccesos;
            $prevSolicitudesUrl = $this->url('/admin/dashboard?' . http_build_query($paramsPrevSolicitudes));
        }

        if ($paginaSolicitudes < $totalPaginasSolicitudes) {
            $paramsNextSolicitudes = $paramsBase;
            $paramsNextSolicitudes['pagina_solicitudes'] = (string) ($paginaSolicitudes + 1);
            $paramsNextSolicitudes['pagina_accesos'] = (string) $paginaAccesos;
            $nextSolicitudesUrl = $this->url('/admin/dashboard?' . http_build_query($paramsNextSolicitudes));
        }

        $desdeAccesos = $totalAccesos > 0 ? (($paginaAccesos - 1) * $porPaginaAccesos + 1) : 0;
        $hastaAccesos = $totalAccesos > 0 ? min($paginaAccesos * $porPaginaAccesos, $totalAccesos) : 0;

        $prevUrl = '';
        $nextUrl = '';

        if ($paginaAccesos > 1) {
            $paramsPrev = $paramsBase;
            $paramsPrev['pagina_accesos'] = (string) ($paginaAccesos - 1);
            $paramsPrev['pagina_solicitudes'] = (string) $paginaSolicitudes;
            $prevUrl = $this->url('/admin/dashboard?' . http_build_query($paramsPrev));
        }

        if ($paginaAccesos < $totalPaginasAccesos) {
            $paramsNext = $paramsBase;
            $paramsNext['pagina_accesos'] = (string) ($paginaAccesos + 1);
            $paramsNext['pagina_solicitudes'] = (string) $paginaSolicitudes;
            $nextUrl = $this->url('/admin/dashboard?' . http_build_query($paramsNext));
        }

        return [
            'resetPasswordAction' => $this->url('/admin/reset-password'),
            'discardResetRequestAction' => $this->url('/admin/discard-reset-request'),
            'solicitudesReset' => $solicitudesReset,
            'totalSolicitudesPendientes' => $becarioModel->contarSolicitudesResetPendientes(),
            'totalSolicitudesFiltradas' => $totalSolicitudesFiltradas,
            'paginaSolicitudes' => $paginaSolicitudes,
            'totalPaginasSolicitudes' => $totalPaginasSolicitudes,
            'desdeSolicitudes' => $desdeSolicitudes,
            'hastaSolicitudes' => $hastaSolicitudes,
            'prevSolicitudesUrl' => $prevSolicitudesUrl,
            'nextSolicitudesUrl' => $nextSolicitudesUrl,
            'filtroBusquedaSolicitudes' => (string) ($filtros['q'] ?? ''),
            'filtroFechaDesde' => (string) ($filtros['fecha_desde'] ?? ''),
            'filtroFechaHasta' => (string) ($filtros['fecha_hasta'] ?? ''),
            'filtroSoloPendientes' => isset($filtros['solo_pendientes']) && (int) $filtros['solo_pendientes'] === 1,
            'listadoEstudiantesAcceso' => $listadoEstudiantesAcceso,
            'totalEstudiantesAcceso' => $totalAccesos,
            'paginaEstudiantesAcceso' => $paginaAccesos,
            'totalPaginasEstudiantesAcceso' => $totalPaginasAccesos,
            'desdeEstudiantesAcceso' => $desdeAccesos,
            'hastaEstudiantesAcceso' => $hastaAccesos,
            'prevEstudiantesAccesoUrl' => $prevUrl,
            'nextEstudiantesAccesoUrl' => $nextUrl,
            'exportEstudiantesAccesoExcelUrl' => $this->url('/admin/export-estudiantes-ultimo-acceso-excel'),
            'exportEstudiantesAccesoPdfUrl' => $this->url('/admin/export-estudiantes-ultimo-acceso-pdf'),
        ];
    }

    private function exportarEstudiantesAccesoCsvFallback(Becario $becarioModel, int $total): void
    {
        $filename = 'reporte_estudiantes_ultimo_acceso_todos_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            http_response_code(500);
            echo 'No fue posible generar el archivo.';
            return;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Cedula', 'Estudiante', 'Nivel', 'Ultimo acceso'], ';');

        $offset = 0;
        $lote = 1000;
        while ($offset < $total) {
            $filas = $becarioModel->obtenerListadoEstudiantesUltimoAccesoPorLote($offset, $lote);
            if (empty($filas)) {
                break;
            }

            foreach ($filas as $fila) {
                $nombres = trim((string) ($fila['nombres'] ?? ''));
                $apellidos = trim((string) ($fila['apellidos'] ?? ''));
                $nombreCompleto = trim($nombres . ' ' . $apellidos);
                $nivel = trim((string) ($fila['nivel'] ?? ''));
                $ultimoAcceso = $this->formatearFechaIngresoConDia((string) ($fila['fecha_ingreso_landing'] ?? ''));

                fputcsv($out, [
                    (string) ($fila['cedula'] ?? ''),
                    $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre',
                    $nivel !== '' ? $nivel : '-',
                    $ultimoAcceso !== '' ? $ultimoAcceso : '-',
                ], ';');
            }

            $offset += count($filas);
        }

        fclose($out);
        exit;
    }

    private function formatearFechaIngresoConDia(string $fechaRaw): string
    {
        $fechaRaw = trim($fechaRaw);
        if ($fechaRaw === '') {
            return '';
        }

        $ts = strtotime($fechaRaw);
        if ($ts === false) {
            return '';
        }

        $dias = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        $dia = $dias[(int) date('w', $ts)] ?? '';
        return trim($dia . ' ' . date('d-m-Y, H:i', $ts));
    }

    private function crearPdfTextoSimple(array $lineas): string
    {
        $porPagina = 45;
        $chunks = array_chunk($lineas, $porPagina);

        $objetos = [];
        $idsPaginas = [];

        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

        $nextId = 4;
        foreach ($chunks as $chunk) {
            $contenidoId = $nextId++;
            $paginaId = $nextId++;

            $idsPaginas[] = $paginaId;

            $streamLines = [];
            $streamLines[] = 'BT';
            $streamLines[] = '/F1 10 Tf';
            $streamLines[] = '14 TL';
            $streamLines[] = '40 800 Td';

            $first = true;
            foreach ($chunk as $linea) {
                $lineaIso = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $linea);
                if ($lineaIso === false) {
                    $lineaIso = preg_replace('/[^\x20-\x7E]/', '?', (string) $linea);
                }

                if (!$first) {
                    $streamLines[] = 'T*';
                }
                $first = false;

                $streamLines[] = '(' . $this->escaparTextoPdf($lineaIso) . ') Tj';
            }

            $streamLines[] = 'ET';
            $stream = implode("\n", $streamLines) . "\n";
            $objetos[$contenidoId] = '<< /Length ' . strlen($stream) . ' >>' . "\nstream\n" . $stream . "endstream";
            $objetos[$paginaId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contenidoId . ' 0 R >>';
        }

        $kids = '';
        foreach ($idsPaginas as $idPagina) {
            $kids .= $idPagina . ' 0 R ';
        }
        $objetos[2] = '<< /Type /Pages /Kids [' . trim($kids) . '] /Count ' . count($idsPaginas) . ' >>';

        ksort($objetos);
        $pdf = "%PDF-1.4\n";
        $offsets = [0 => 0];

        foreach ($objetos as $id => $content) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $content . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $maxId = max(array_keys($objetos));
        $pdf .= 'xref' . "\n";
        $pdf .= '0 ' . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxId; $i++) {
            $off = $offsets[$i] ?? 0;
            $pdf .= sprintf('%010d 00000 n ', $off) . "\n";
        }

        $pdf .= 'trailer << /Size ' . ($maxId + 1) . ' /Root 1 0 R >>' . "\n";
        $pdf .= 'startxref' . "\n" . $xrefPos . "\n%%EOF";

        return $pdf;
    }

    private function escaparTextoPdf(string $texto): string
    {
        $texto = str_replace('\\', '\\\\', $texto);
        $texto = str_replace('(', '\\(', $texto);
        $texto = str_replace(')', '\\)', $texto);
        return $texto;
    }

    private function obtenerFiltrosSolicitudesDesdeRequest(): array
    {
        $fechaDesde = trim((string) ($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string) ($_GET['fecha_hasta'] ?? ''));
        $busqueda = trim((string) ($_GET['q'] ?? ''));
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

        if (mb_strlen($busqueda) > 120) {
            $busqueda = mb_substr($busqueda, 0, 120);
        }

        return [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'solo_pendientes' => $soloPendientes ? 1 : 0,
            'q' => $busqueda,
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
