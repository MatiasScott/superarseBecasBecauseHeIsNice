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
    <title>Olvide mi contrasena - Instituto Superarse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= h($assetCssPath ?? '') ?>">
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4" data-base-path="<?= h($basePath ?? '') ?>">
    <div class="bg-white rounded-2xl shadow-2xl w-[90%] max-w-3xl p-8 md:p-10 space-y-6">
        <div class="text-center">
            <h1 class="text-3xl md:text-4xl font-extrabold text-blue-700">Recuperacion de contrasena</h1>
            <p class="text-gray-600 mt-2">Solicita el reseteo de tu acceso de estudiante.</p>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-900">
            La solicitud sera revisada por el area administrativa para resetear el acceso.
        </div>

        <?php if (!empty($message)) : ?>
            <div class="rounded-xl p-4 border <?= ($messageType ?? '') === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-700' ?>">
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">

            <div>
                <label for="cedula" class="block text-sm font-bold text-gray-700 mb-2">Cedula del estudiante</label>
                <input id="cedula" name="cedula" type="text" maxlength="10" required
                    class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ingresa cedula de 10 digitos">
            </div>

            <button type="submit"
                class="w-full md:w-auto bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-200 shadow-md">
                Enviar solicitud
            </button>
        </form>

        <div class="flex pt-2">
            <a href="<?= h($homeUrl ?? '#') ?>" class="text-sm font-semibold text-blue-700 hover:underline">
                <i class="bi bi-arrow-left mr-1"></i> Volver al inicio
            </a>
        </div>
    </div>
</body>

</html>
