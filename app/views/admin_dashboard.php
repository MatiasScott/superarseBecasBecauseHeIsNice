<?php
function h($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard administrativo - Instituto Superarse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= h($assetCssPath ?? '') ?>">
</head>

<body class="min-h-screen bg-gray-50 p-4 md:p-6" data-base-path="<?= h($basePath ?? '') ?>">
    <?php
    $tab = (string) ($_GET['tab'] ?? 'resumen');
    $tabsPermitidos = ['resumen', 'estudiantes', 'admins', 'certificados'];
    if (!in_array($tab, $tabsPermitidos, true)) {
        $tab = 'resumen';
    }
    ?>

    <div class="w-[95%] mx-auto lg:grid lg:grid-cols-[280px_1fr] lg:gap-6 space-y-5 lg:space-y-0">
        <aside class="lg:sticky lg:top-6 h-fit bg-white rounded-2xl shadow-2xl p-4 md:p-5 space-y-4">
            <div>
                <h1 class="text-2xl font-extrabold text-blue-700">Panel Admin</h1>
                <p class="text-sm text-gray-600 mt-1">Sesión: <?= h($adminUsuario ?? '') ?></p>
            </div>

            <nav class="space-y-2">
                <a href="?tab=resumen" class="w-full inline-flex items-center gap-2 py-2.5 px-3 rounded-lg font-semibold <?= $tab === 'resumen' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <i class="bi bi-speedometer2"></i> Resumen
                </a>
                <a href="?tab=estudiantes" class="w-full inline-flex items-center gap-2 py-2.5 px-3 rounded-lg font-semibold <?= $tab === 'estudiantes' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <i class="bi bi-people-fill"></i> Estudiantes
                </a>
                <a href="?tab=admins" class="w-full inline-flex items-center gap-2 py-2.5 px-3 rounded-lg font-semibold <?= $tab === 'admins' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <i class="bi bi-person-badge-fill"></i> Cuentas Admin
                </a>
                <a href="?tab=certificados" class="w-full inline-flex items-center gap-2 py-2.5 px-3 rounded-lg font-semibold <?= $tab === 'certificados' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                    <i class="bi bi-award-fill"></i> Certificados
                </a>
            </nav>

            <div class="pt-2 border-t border-gray-200 space-y-2">
                <a href="<?= h($adminChangePasswordUrl ?? '#') ?>" class="w-full inline-flex items-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold py-2 px-3 rounded-lg">
                    <i class="bi bi-key-fill"></i>
                    Cambiar contraseña
                </a>
                <form method="POST" action="<?= h($adminLogoutUrl ?? '#') ?>" class="m-0">
                    <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">
                    <button type="submit" class="w-full inline-flex items-center gap-2 bg-red-100 hover:bg-red-200 text-red-700 font-semibold py-2 px-3 rounded-lg">
                        <i class="bi bi-box-arrow-right"></i>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <main class="space-y-5">
            <?php if (!empty($flash) && is_array($flash)) : ?>
                <div class="rounded-xl p-4 border <?= ($flash['type'] ?? '') === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700' ?>">
                    <?= h($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'resumen') : ?>
                <div class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-5">
                <h2 class="text-2xl font-bold text-gray-800">Resumen general</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-sm text-blue-700 font-semibold">Total estudiantes</p>
                        <p class="text-3xl font-extrabold text-blue-900"><?= h($resumenEstudiantes['total_estudiantes'] ?? 0) ?></p>
                    </div>
                    <div class="rounded-xl border border-green-100 bg-green-50 p-4">
                        <p class="text-sm text-green-700 font-semibold">Perfiles completos</p>
                        <p class="text-3xl font-extrabold text-green-900"><?= h($resumenEstudiantes['perfiles_completos'] ?? 0) ?></p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                        <p class="text-sm text-amber-700 font-semibold">Perfiles pendientes</p>
                        <p class="text-3xl font-extrabold text-amber-900"><?= h($resumenEstudiantes['perfiles_pendientes'] ?? 0) ?></p>
                    </div>
                    <div class="rounded-xl border border-purple-100 bg-purple-50 p-4">
                        <p class="text-sm text-purple-700 font-semibold">Total certificados</p>
                        <p class="text-3xl font-extrabold text-purple-900"><?= h($resumenCertificados['total_certificados'] ?? 0) ?></p>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                    <h3 class="font-bold text-gray-800 mb-2">Estado de contraseñas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                        <p class="bg-white border rounded-lg px-3 py-2 flex items-center justify-between"><span>Inicial:</span><strong><?= h($estadoContrasenias['contrasenias_iniciales'] ?? 0) ?></strong></p>
                        <p class="bg-white border rounded-lg px-3 py-2 flex items-center justify-between"><span>Personalizada:</span><strong><?= h($estadoContrasenias['contrasenias_personalizadas'] ?? 0) ?></strong></p>
                        <p class="bg-white border rounded-lg px-3 py-2 flex items-center justify-between"><span>Sin contraseña:</span><strong><?= h($estadoContrasenias['sin_password_login'] ?? 0) ?></strong></p>
                    </div>
                </div>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'estudiantes') : ?>
                <div class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-4 max-w-2xl">
                <h2 class="text-2xl font-bold text-gray-800">Gestión de estudiantes</h2>
                <p class="text-sm text-gray-600">Resetea la contraseña de login de estudiantes. La nueva contraseña temporal será su cédula.</p>
                <form method="POST" action="<?= h($resetPasswordAction ?? '#') ?>" class="space-y-3">
                    <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">
                    <label for="cedula" class="block text-sm font-semibold text-gray-700">Cédula</label>
                    <input id="cedula" name="cedula" type="text" maxlength="10" required
                        class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="10 digitos">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 rounded-lg focus:outline-none focus:ring-4 focus:ring-red-300 shadow-md">
                        Resetear contraseña de estudiante
                    </button>
                </form>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'admins') : ?>
                <div class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-4 max-w-2xl">
                <h2 class="text-2xl font-bold text-gray-800">Cuentas administrativas</h2>
                <p class="text-sm text-gray-600">Crea usuarios nuevos para personal autorizado.</p>

                <form method="POST" action="<?= h($createAdminAction ?? '#') ?>" class="space-y-3">
                    <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">

                    <div>
                        <label for="admin_usuario" class="block text-sm font-semibold text-gray-700 mb-1">Usuario</label>
                        <input id="admin_usuario" name="usuario" type="text" required minlength="4" maxlength="60"
                            class="w-full border rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="ejemplo.admin">
                    </div>

                    <div>
                        <label for="admin_nombre" class="block text-sm font-semibold text-gray-700 mb-1">Nombre (opcional)</label>
                        <input id="admin_nombre" name="nombre" type="text" maxlength="120"
                            class="w-full border rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Nombre del administrador">
                    </div>

                    <div>
                        <label for="admin_password" class="block text-sm font-semibold text-gray-700 mb-1">Contraseña</label>
                        <input id="admin_password" name="password" type="password" required
                            class="w-full border rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Contraseña segura">
                    </div>

                    <div>
                        <label for="admin_confirm_password" class="block text-sm font-semibold text-gray-700 mb-1">Confirmar contraseña</label>
                        <input id="admin_confirm_password" name="confirm_password" type="password" required
                            class="w-full border rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Repite la contraseña">
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="primer_inicio" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Exigir cambio de contraseña en primer inicio
                    </label>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-2.5 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-md">
                        Crear cuenta admin
                    </button>
                </form>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'certificados') : ?>
                <div class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-5">
                <h2 class="text-2xl font-bold text-gray-800">Módulo de certificados</h2>

                <div class="rounded-2xl border border-gray-200 p-5 space-y-4">
                    <h3 class="text-xl font-bold text-gray-800">Certificados por nivel</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <?php foreach (($niveles ?? []) as $nivel) : ?>
                            <div class="rounded-xl p-4 bg-gray-50 border border-gray-200 text-center">
                                <p class="text-sm font-semibold text-gray-600">Nivel <?= h($nivel) ?></p>
                                <p class="text-2xl font-extrabold text-gray-800"><?= h($conteoNiveles[$nivel] ?? 0) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 p-5 space-y-4">
                    <h3 class="text-xl font-bold text-gray-800">Últimas cargas</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2 pr-3">Cédula</th>
                                    <th class="py-2 pr-3">Nivel</th>
                                    <th class="py-2 pr-3">Fecha</th>
                                    <th class="py-2">Archivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ultimasCargas)) : ?>
                                    <tr>
                                        <td colspan="4" class="py-3 text-gray-500">No hay cargas registradas.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($ultimasCargas as $fila) : ?>
                                        <tr class="border-b last:border-b-0">
                                            <td class="py-2 pr-3 font-semibold text-gray-700"><?= h($fila['cedula'] ?? '') ?></td>
                                            <td class="py-2 pr-3"><?= h($fila['nivel'] ?? '') ?></td>
                                            <td class="py-2 pr-3"><?= h($fila['fecha_subida'] ?? '') ?></td>
                                            <td class="py-2 break-all text-xs text-gray-600"><?= h($fila['ruta_archivo'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>
