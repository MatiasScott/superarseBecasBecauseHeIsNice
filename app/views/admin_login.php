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
    <title>Login admin - Instituto Superarse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= h($assetCssPath ?? '') ?>">
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4" data-base-path="<?= h($basePath ?? '') ?>">
    <div class="bg-white rounded-2xl shadow-2xl w-[90%] max-w-xl p-8 md:p-10 space-y-6">
        <div class="text-center">
            <h1 class="text-3xl font-extrabold text-blue-700">Acceso administrativo</h1>
            <p class="text-gray-600 mt-2">Uso exclusivo para administradores autorizados.</p>
        </div>

        <?php if (!empty($errorMessage)) : ?>
            <div class="rounded-xl p-4 border bg-red-50 border-red-200 text-red-700">
                <?= h($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">

            <div>
                <label for="usuario" class="block text-sm font-bold text-gray-700 mb-2">Usuario admin</label>
                <input id="usuario" name="usuario" type="text" required
                    class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ingresa tu usuario">
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Contrasena</label>
                <input id="password" name="password" type="password" required
                    class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ingresa tu contrasena">
            </div>

            <button type="submit"
                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-200 shadow-md">
                Ingresar al dashboard
            </button>
        </form>

        <a href="<?= h((defined('BASE_URL') ? BASE_URL : '') . '/') ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-800 hover:underline">
            <i class="bi bi-arrow-left"></i> Volver al inicio
        </a>
    </div>
</body>

</html>
