<section class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-5">
    <h2 class="text-2xl font-bold text-gray-800">Modulo de certificados</h2>

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
        <h3 class="text-xl font-bold text-gray-800">Ultimas cargas</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2 pr-3">Cedula</th>
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
</section>
