<?php
// La función h() es opcional aquí si ya la definiste en el controlador,
// pero es buena práctica tenerla para sanitizar las salidas.
function h($value)
{
    return htmlspecialchars($value ?? '');
}

// Obtener la ruta base de la aplicación dinámicamente
// $_SERVER['SCRIPT_NAME'] es la ruta del script actual, ej. /ISuperarse/public/index.php
// dirname() obtiene el directorio padre, ej. /ISuperarse/public
$basePath = dirname($_SERVER['SCRIPT_NAME']);
// Aseguramos que la basePath tenga una barra al final si no está vacía
if (!empty($basePath) && substr($basePath, -1) !== '/') {
    $basePath .= '/';
}
// Si la basePath es solo "/", la dejamos así, sino la ajustamos si es necesario (ej. para XAMPP en localhost)
if ($basePath === '/' && strpos($_SERVER['REQUEST_URI'], '/ISuperarse/public') !== false) {
    // Esto es un ajuste específico si index.php está directamente en public/
    // y el acceso es vía /ISuperarse/public/
    $basePath = '/ISuperarse/public/';
} elseif (empty($basePath)) {
    // Si la aplicación está en la raíz del dominio
    $basePath = '/';
}
?>
<div id="modal-form-content" class="p-6 max-w-lg mx-auto rounded-xl shadow-lg space-y-4">
    <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">Completa tu información</h2>
    <!-- CAMBIO CLAVE AQUÍ: El action del formulario ahora usa la $basePath dinámica -->
    <form action="<?= $basePath ?>becario/procesar" method="POST" class="space-y-4">
        <!-- Campo oculto para enviar la cédula, crucial para el procesamiento -->
        <input type="hidden" name="cedula" value="<?= htmlspecialchars($data['becario']['cedula']) ?>">

        <!-- Campos de información personal (solo lectura) -->
        <div>
            <label for="nombres" class="block text-gray-700 text-sm font-bold mb-2">Nombres:</label>
            <input type="text" id="nombres" name="nombres" value="<?= htmlspecialchars($data['becario']['nombres']) ?>" readonly
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100 cursor-not-allowed">
        </div>

        <div>
            <label for="apellidos" class="block text-gray-700 text-sm font-bold mb-2">Apellidos:</label>
            <input type="text" id="apellidos" name="apellidos" value="<?= htmlspecialchars($data['becario']['apellidos']) ?>" readonly
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100 cursor-not-allowed">
        </div>

        <div>
            <label for="correo" class="block text-gray-700 text-sm font-bold mb-2">Correo:</label>
            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($data['becario']['correo']) ?>" readonly
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100 cursor-not-allowed">
        </div>

        <!-- Campo de Ciudad -->
        <div>
            <label for="ciudad" class="block text-gray-700 text-sm font-bold mb-2">Ciudad:</label>
            <input type="text" id="ciudad" name="ciudad" value="<?= htmlspecialchars($data['becario']['ciudad'] ?? '') ?>"
                   <?= (!empty($data['becario']['ciudad'])) ? 'readonly' : '' ?> required
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline
                   <?= (!empty($data['becario']['ciudad'])) ? 'bg-gray-100 cursor-not-allowed' : '' ?>">
        </div>

        <!-- Campo de Provincia (condicional: input o select) -->
        <div>
            <label for="provincia" class="block text-gray-700 text-sm font-bold mb-2">Provincia:</label>
            <?php if (!empty($data['becario']['provincia'])) : ?>
                <!-- Si ya tiene provincia, se muestra como solo lectura -->
                <input type="text" id="provincia" name="provincia" value="<?= htmlspecialchars($data['becario']['provincia']) ?>" readonly required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline bg-gray-100 cursor-not-allowed">
            <?php else: ?>
                <!-- Si no tiene provincia, se muestra un desplegable para seleccionarla -->
                <select id="provincia" name="provincia" required
                        class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Seleccione una provincia</option>
                    <?php foreach ($data['provincias'] as $prov) : ?>
                        <option value="<?= htmlspecialchars($prov) ?>"><?= htmlspecialchars($prov) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <!-- Botón de envío dinámico -->
        <?php
        $botonTexto = (!empty($data['becario']['ciudad']) && !empty($data['becario']['provincia'])) ? 'Continuar' : 'Guardar';
        ?>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline w-full transform transition-transform duration-200 hover:scale-105">
            <?= htmlspecialchars($botonTexto) ?>
        </button>
    </form>
</div>
