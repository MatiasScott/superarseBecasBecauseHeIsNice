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
        <?php require BASE_PATH . '/app/views/admin/partials/sidebar.php'; ?>

        <main class="space-y-5">
            <?php require BASE_PATH . '/app/views/admin/partials/header.php'; ?>

            <?php if (!empty($flash) && is_array($flash)) : ?>
                <div class="rounded-xl p-4 border <?= ($flash['type'] ?? '') === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700' ?>">
                    <?= h($flash['message'] ?? '') ?>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'resumen') : ?>
                <?php require BASE_PATH . '/app/views/admin/sections/resumen.php'; ?>
            <?php endif; ?>

            <?php if ($tab === 'estudiantes') : ?>
                <?php require BASE_PATH . '/app/views/admin/sections/estudiantes.php'; ?>
            <?php endif; ?>

            <?php if ($tab === 'admins') : ?>
                <?php require BASE_PATH . '/app/views/admin/sections/cuentas_admin.php'; ?>
            <?php endif; ?>

            <?php if ($tab === 'certificados') : ?>
                <?php require BASE_PATH . '/app/views/admin/sections/certificados.php'; ?>
            <?php endif; ?>

            <?php require BASE_PATH . '/app/views/admin/partials/footer.php'; ?>
        </main>
    </div>
</body>

</html>
