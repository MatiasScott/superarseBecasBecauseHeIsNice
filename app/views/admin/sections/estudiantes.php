<section class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Gestion de estudiantes</h2>
    <p class="text-sm text-gray-600">Resetea la contraseña de login de estudiantes. La nueva contraseña temporal sera su cedula.</p>

    <form method="GET" action="" class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">
        <input type="hidden" name="tab" value="estudiantes">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label for="q" class="block text-xs font-semibold text-gray-600 mb-1">Buscar por cédula o nombre</label>
                <input id="q" name="q" type="text" value="<?= h($filtroBusquedaSolicitudes ?? '') ?>" placeholder="Ej: 1234567890 o Juan"
                    class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
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
                    <input type="checkbox" name="solo_pendientes" value="1" <?= ($filtroSoloPendientes ?? false) ? 'checked' : '' ?>
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

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <p class="text-xs text-gray-600">
                    Mostrando <?= (int) ($desdeSolicitudes ?? 0) ?> - <?= (int) ($hastaSolicitudes ?? 0) ?>
                    de <?= (int) ($totalSolicitudesFiltradas ?? 0) ?> solicitudes
                    (página <?= (int) ($paginaSolicitudes ?? 1) ?> de <?= (int) ($totalPaginasSolicitudes ?? 1) ?>)
                </p>
                <div class="flex items-center gap-2">
                    <?php if (!empty($prevSolicitudesUrl)) : ?>
                        <a href="<?= h($prevSolicitudesUrl) ?>"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="bi bi-chevron-left"></i>
                            Anterior
                        </a>
                    <?php else : ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-400 cursor-not-allowed">
                            <i class="bi bi-chevron-left"></i>
                            Anterior
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($nextSolicitudesUrl)) : ?>
                        <a href="<?= h($nextSolicitudesUrl) ?>"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-100">
                            Siguiente
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else : ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-400 cursor-not-allowed">
                            Siguiente
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <p class="text-sm text-gray-600">No hay solicitudes para los filtros seleccionados.</p>
        <?php endif; ?>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Listado completo de estudiantes - Ultimo acceso</h3>
                <p class="text-sm text-gray-600">Muestra cedula, nombre, nivel y la ultima fecha/hora de ingreso a la landing.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?= h($exportEstudiantesAccesoExcelUrl ?? '#') ?>"
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm py-2 px-4 rounded-lg">
                    <i class="bi bi-file-earmark-excel"></i>
                    Exportar Excel (todos)
                </a>
                <a href="<?= h($exportEstudiantesAccesoPdfUrl ?? '#') ?>"
                    class="inline-flex items-center gap-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm py-2 px-4 rounded-lg">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Exportar PDF (todos)
                </a>
            </div>
        </div>

        <?php if (!empty($listadoEstudiantesAcceso)) : ?>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="py-2 px-3">Cedula</th>
                            <th class="py-2 px-3">Estudiante</th>
                            <th class="py-2 px-3">Nivel</th>
                            <th class="py-2 px-3">Ultimo acceso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listadoEstudiantesAcceso as $filaAcceso) : ?>
                            <?php
                            $nombresAcceso = trim((string) ($filaAcceso['nombres'] ?? ''));
                            $apellidosAcceso = trim((string) ($filaAcceso['apellidos'] ?? ''));
                            $nombreCompletoAcceso = trim($nombresAcceso . ' ' . $apellidosAcceso);
                            $nivelAcceso = trim((string) ($filaAcceso['nivel'] ?? ''));
                            $ingresoRaw = trim((string) ($filaAcceso['fecha_ingreso_landing'] ?? ''));
                            $ingresoFmt = '-';
                            if ($ingresoRaw !== '') {
                                $tsIngreso = strtotime($ingresoRaw);
                                if ($tsIngreso !== false) {
                                    $dias = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
                                    $dia = $dias[(int) date('w', $tsIngreso)] ?? '';
                                    $ingresoFmt = trim($dia . ' ' . date('d-m-Y, H:i', $tsIngreso));
                                }
                            }
                            ?>
                            <tr class="border-t border-gray-100">
                                <td class="py-2 px-3 font-mono font-semibold text-gray-700"><?= h($filaAcceso['cedula'] ?? '') ?></td>
                                <td class="py-2 px-3 text-gray-700"><?= h($nombreCompletoAcceso !== '' ? $nombreCompletoAcceso : 'Sin nombre') ?></td>
                                <td class="py-2 px-3">
                                    <span class="inline-block text-xs font-semibold px-2 py-1 rounded-full bg-blue-100 text-blue-700">
                                        <?= h($nivelAcceso !== '' ? $nivelAcceso : '-') ?>
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-gray-600"><?= h($ingresoFmt) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                <p class="text-xs text-gray-600">
                    Mostrando <?= (int) ($desdeEstudiantesAcceso ?? 0) ?> - <?= (int) ($hastaEstudiantesAcceso ?? 0) ?>
                    de <?= (int) ($totalEstudiantesAcceso ?? 0) ?> estudiantes
                    (página <?= (int) ($paginaEstudiantesAcceso ?? 1) ?> de <?= (int) ($totalPaginasEstudiantesAcceso ?? 1) ?>)
                </p>
                <div class="flex items-center gap-2">
                    <?php if (!empty($prevEstudiantesAccesoUrl)) : ?>
                        <a href="<?= h($prevEstudiantesAccesoUrl) ?>"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="bi bi-chevron-left"></i>
                            Anterior
                        </a>
                    <?php else : ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-400 cursor-not-allowed">
                            <i class="bi bi-chevron-left"></i>
                            Anterior
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($nextEstudiantesAccesoUrl)) : ?>
                        <a href="<?= h($nextEstudiantesAccesoUrl) ?>"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-100">
                            Siguiente
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else : ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-400 cursor-not-allowed">
                            Siguiente
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <p class="text-sm text-gray-600">No hay estudiantes registrados para mostrar.</p>
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
