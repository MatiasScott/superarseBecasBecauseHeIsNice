<section class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Gestion de estudiantes</h2>
    <p class="text-sm text-gray-600">Resetea la contraseña de login de estudiantes. La nueva contraseña temporal sera su cedula.</p>

    <form method="GET" action="" class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">
        <input type="hidden" name="tab" value="estudiantes">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label for="fecha_desde" class="block text-xs font-semibold text-gray-600 mb-1">Fecha desde</label>
                <input id="fecha_desde" name="fecha_desde" type="date" value="<?= h($filtroFechaDesde ?? '') ?>"
                    class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-xs font-semibold text-gray-600 mb-1">Fecha hasta</label>
                <input id="fecha_hasta" name="fecha_hasta" type="date" value="<?= h($filtroFechaHasta ?? '') ?>"
                    class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="solo_pendientes" value="1" <?= !empty($filtroSoloPendientes) ? 'checked' : '' ?>
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Solo pendientes
                </label>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="submit"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-300">
                <i class="bi bi-funnel-fill"></i>
                Filtrar
            </button>
            <a href="?tab=estudiantes"
                class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm py-2 px-4 rounded-lg">
                Limpiar filtros
            </a>
        </div>
    </form>

    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold text-gray-800">Solicitudes de reseteo</h3>
            <span class="text-xs font-semibold px-2 py-1 rounded-full bg-blue-100 text-blue-700">
                <?= (int) ($totalSolicitudesPendientes ?? 0) ?> pendientes
            </span>
        </div>

        <?php if (!empty($solicitudesReset)) : ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-200">
                            <th class="py-2 pr-3">Cedula</th>
                            <th class="py-2 pr-3">Estudiante</th>
                            <th class="py-2 pr-3">Estado</th>
                            <th class="py-2 pr-3">Fecha solicitud</th>
                            <th class="py-2 pr-3">Gestionado por</th>
                            <th class="py-2">Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudesReset as $solicitud) : ?>
                            <tr class="border-b border-gray-100 align-top">
                                <td class="py-2 pr-3 font-semibold text-gray-700"><?= h($solicitud['cedula'] ?? '') ?></td>
                                <td class="py-2 pr-3 text-gray-700">
                                    <?php
                                    $nombres = trim((string) ($solicitud['nombres'] ?? ''));
                                    $apellidos = trim((string) ($solicitud['apellidos'] ?? ''));
                                    $nombreCompleto = trim($nombres . ' ' . $apellidos);
                                    $estado = (string) ($solicitud['estado'] ?? 'pendiente');
                                    ?>
                                    <?= h($nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre disponible') ?>
                                </td>
                                <td class="py-2 pr-3">
                                    <?php if ($estado === 'pendiente') : ?>
                                        <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full bg-amber-100 text-amber-700">Pendiente</span>
                                    <?php elseif ($estado === 'atendida') : ?>
                                        <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-700">Atendida</span>
                                    <?php else : ?>
                                        <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full bg-gray-200 text-gray-700">Descartada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 pr-3 text-gray-600"><?= h($solicitud['solicitado_en'] ?? '') ?></td>
                                <td class="py-2 pr-3 text-gray-600">
                                    <?php
                                    $gestion = trim((string) ($solicitud['atendido_por_usuario'] ?? ''));
                                    $gestionFecha = trim((string) ($solicitud['atendido_en'] ?? ''));
                                    ?>
                                    <?php if ($gestion !== '' || $gestionFecha !== '') : ?>
                                        <?= h(($gestion !== '' ? $gestion : 'admin') . ($gestionFecha !== '' ? (' - ' . $gestionFecha) : '')) ?>
                                    <?php else : ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-2">
                                    <?php if ($estado === 'pendiente') : ?>
                                        <div class="flex flex-wrap gap-2">
                                            <form method="POST" action="<?= h($resetPasswordAction ?? '#') ?>" class="inline">
                                                <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">
                                                <input type="hidden" name="cedula" value="<?= h($solicitud['cedula'] ?? '') ?>">
                                                <input type="hidden" name="request_id" value="<?= h($solicitud['id'] ?? '') ?>">
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold text-xs px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                    Resetear
                                                </button>
                                            </form>

                                            <form method="POST" action="<?= h($discardResetRequestAction ?? '#') ?>" class="inline">
                                                <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">
                                                <input type="hidden" name="request_id" value="<?= h($solicitud['id'] ?? '') ?>">
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-gray-600 hover:bg-gray-700 text-white font-semibold text-xs px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                                    <i class="bi bi-x-circle"></i>
                                                    Descartar
                                                </button>
                                            </form>
                                        </div>
                                    <?php else : ?>
                                        <span class="text-gray-400 text-xs">Sin acciones</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <p class="text-sm text-gray-600">No hay solicitudes para los filtros seleccionados.</p>
        <?php endif; ?>
    </div>

    <div class="border-t border-gray-100 pt-4">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Reseteo manual por cedula</h3>
        <p class="text-sm text-gray-600 mb-3">Tambien puedes resetear manualmente si no existe solicitud registrada.</p>
    </div>

    <form method="POST" action="<?= h($resetPasswordAction ?? '#') ?>" class="space-y-3">
        <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">
        <label for="cedula" class="block text-sm font-semibold text-gray-700">Cedula</label>
        <input id="cedula" name="cedula" type="text" maxlength="10" required
            class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="10 digitos">
        <button type="submit"
            class="w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold py-3 rounded-lg focus:outline-none focus:ring-4 focus:ring-red-300 shadow-md">
            Resetear contraseña de estudiante
        </button>
    </form>
</section>
