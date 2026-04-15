<aside class="lg:sticky lg:top-6 h-fit bg-white rounded-2xl shadow-2xl p-4 md:p-5 space-y-4">
    <div>
        <h2 class="text-2xl font-extrabold text-blue-700">Panel Admin</h2>
        <p class="text-sm text-gray-600 mt-1">Navegacion del modulo</p>
    </div>

    <nav class="space-y-2">
        <a href="?tab=resumen" class="w-full inline-flex items-center gap-2 py-2.5 px-3 rounded-lg font-semibold <?= $tab === 'resumen' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="bi bi-speedometer2"></i> Resumen
        </a>
        <a href="?tab=estudiantes" class="w-full inline-flex items-center gap-2 py-2.5 px-3 rounded-lg font-semibold <?= $tab === 'estudiantes' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="bi bi-people-fill"></i> Estudiantes
        </a>
        <a href="?tab=admins" class="w-full inline-flex items-center gap-2 py-2.5 px-3 rounded-lg font-semibold <?= $tab === 'admins' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="bi bi-person-badge-fill"></i> Cuentas Admin
        </a>
        <a href="?tab=certificados" class="w-full inline-flex items-center gap-2 py-2.5 px-3 rounded-lg font-semibold <?= $tab === 'certificados' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            <i class="bi bi-award-fill"></i> Certificados
        </a>
    </nav>
</aside>
