<div id="modal-form-content" class="p-6 max-w-lg mx-auto rounded-2xl shadow-xl space-y-4 border border-gray-100 bg-white">
    <h2 class="text-2xl font-bold text-gray-800 text-center mb-6">Completa tu información</h2>
    <form action="<?= htmlspecialchars($formAction ?? '') ?>" method="POST" class="space-y-4">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <!-- Campo oculto para enviar la cédula, crucial para el procesamiento -->
        <input type="hidden" name="cedula" value="<?= htmlspecialchars($data['becario']['cedula']) ?>">

        <!-- Campos de información personal (solo lectura) -->
        <div>
            <label for="nombres" class="block text-gray-700 text-sm font-bold mb-2">Nombres:</label>
                 <input type="text" id="nombres" name="nombres" value="<?= htmlspecialchars($data['becario']['nombres']) ?>" readonly
                     class="shadow-sm appearance-none border rounded-lg w-full py-2.5 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400 bg-gray-100 cursor-not-allowed">
        </div>

        <div>
            <label for="apellidos" class="block text-gray-700 text-sm font-bold mb-2">Apellidos:</label>
                 <input type="text" id="apellidos" name="apellidos" value="<?= htmlspecialchars($data['becario']['apellidos']) ?>" readonly
                     class="shadow-sm appearance-none border rounded-lg w-full py-2.5 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400 bg-gray-100 cursor-not-allowed">
        </div>

        <div>
            <label for="correo" class="block text-gray-700 text-sm font-bold mb-2">Correo:</label>
                 <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($data['becario']['correo']) ?>" readonly
                     class="shadow-sm appearance-none border rounded-lg w-full py-2.5 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400 bg-gray-100 cursor-not-allowed">
        </div>

        <!-- Campo de Ciudad -->
        <div>
            <label for="ciudad" class="block text-gray-700 text-sm font-bold mb-2">Ciudad:</label>
            <input type="text" id="ciudad" name="ciudad" value="<?= htmlspecialchars($data['becario']['ciudad'] ?? '') ?>"
                   <?= (!empty($data['becario']['ciudad'])) ? 'readonly' : '' ?> required
                   class="shadow-sm appearance-none border rounded-lg w-full py-2.5 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400
                   <?= (!empty($data['becario']['ciudad'])) ? 'bg-gray-100 cursor-not-allowed' : '' ?>">
        </div>

        <!-- Campo de Provincia (condicional: input o select) -->
        <div>
            <label for="provincia" class="block text-gray-700 text-sm font-bold mb-2">Provincia:</label>
            <?php if (!empty($data['becario']['provincia'])) : ?>
                <!-- Si ya tiene provincia, se muestra como solo lectura -->
                <input type="text" id="provincia" name="provincia" value="<?= htmlspecialchars($data['becario']['provincia']) ?>" readonly required
                       class="shadow-sm appearance-none border rounded-lg w-full py-2.5 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400 bg-gray-100 cursor-not-allowed">
            <?php else: ?>
                <!-- Si no tiene provincia, se muestra un desplegable para seleccionarla -->
                <select id="provincia" name="provincia" required
                    class="shadow-sm border rounded-lg w-full py-2.5 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400">
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
        <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-2.5 px-4 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300 w-full transform transition-transform duration-200 hover:scale-105 shadow-md">
            <?= htmlspecialchars($botonTexto) ?>
        </button>
    </form>
</div>
