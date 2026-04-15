<section class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Cuentas administrativas</h2>
        <p class="text-sm text-gray-600">Crea usuarios nuevos para personal autorizado.</p>
    </div>

    <div class="space-y-4 max-w-2xl border-b pb-5">
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

    <div>
        <h3 class="text-lg font-bold text-gray-800 mb-3">Cuentas existentes</h3>
        <div class="overflow-x-auto border rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr class="text-left">
                        <th class="py-3 px-4 font-semibold text-gray-700">Usuario</th>
                        <th class="py-3 px-4 font-semibold text-gray-700">Nombre</th>
                        <th class="py-3 px-4 font-semibold text-gray-700">Estado</th>
                        <th class="py-3 px-4 font-semibold text-gray-700">Creado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($admins)) : ?>
                        <tr>
                            <td colspan="4" class="py-4 px-4 text-center text-gray-500">No hay cuentas admin creadas aún.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($admins as $row) : ?>
                            <tr class="border-b last:border-b-0 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-4 font-semibold text-gray-800"><?= h($row['usuario'] ?? '') ?></td>
                                <td class="py-3 px-4 text-gray-700"><?= h($row['nombre'] ?? '-') ?></td>
                                <td class="py-3 px-4">
                                    <?php if ((int)($row['primer_inicio'] ?? 0) === 1) : ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            <i class="bi bi-exclamation-circle"></i> Pendiente
                                        </span>
                                    <?php else : ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            <i class="bi bi-check-circle"></i> Completado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-gray-600 text-xs"><?= h($row['creado_en'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
