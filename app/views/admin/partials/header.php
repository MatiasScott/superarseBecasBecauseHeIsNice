<header class="bg-white rounded-2xl shadow-2xl p-5 md:p-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl md:text-4xl font-extrabold text-blue-700">Dashboard administrativo</h1>
        <p class="text-gray-600 mt-1">Gestion de contrasenas y certificados en modulos separados.</p>
        <p class="text-xs text-gray-500 mt-1">Sesion: <?= h($adminUsuario ?? '') ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= h($adminChangePasswordUrl ?? '#') ?>" class="inline-flex items-center gap-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-semibold py-2 px-4 rounded-lg">
            <i class="bi bi-key-fill"></i>
            Cambiar contrasena
        </a>
        <form method="POST" action="<?= h($adminLogoutUrl ?? '#') ?>" class="m-0">
            <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">
            <button type="submit" class="inline-flex items-center gap-2 bg-red-100 hover:bg-red-200 text-red-700 font-semibold py-2 px-4 rounded-lg">
                <i class="bi bi-box-arrow-right"></i>
                Cerrar sesion
            </button>
        </form>
    </div>
</header>
