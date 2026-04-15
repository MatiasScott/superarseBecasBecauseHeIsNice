<section class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-5">
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
</section>
