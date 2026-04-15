<div class="p-6 space-y-4 max-w-lg mx-auto">
    <div class="text-center mb-2">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full mb-3">
            <i class="bi bi-key text-white text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Cambia tu contraseña</h2>
        <p class="text-gray-500 text-sm mt-1">Es tu primer acceso. Crea una contraseña segura para continuar.</p>
    </div>

    <form id="change-password-form" class="space-y-4">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <input type="hidden" name="cedula" value="<?= htmlspecialchars($cedula ?? '') ?>">

        <!-- Contraseña actual -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Contraseña actual</label>
            <div class="relative">
                <input type="password" id="cp-current-password" name="current_password" required
                    placeholder="Tu contraseña actual"
                    class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition">
                <button type="button" class="cp-toggle absolute inset-y-0 right-0 px-4 text-gray-500 hover:text-gray-700"
                    data-target="cp-current-password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <!-- Nueva contraseña -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Nueva contraseña</label>
            <div class="relative">
                <input type="password" id="cp-new-password" name="new_password" required
                    placeholder="Nueva contraseña segura"
                    class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition">
                <button type="button" class="cp-toggle absolute inset-y-0 right-0 px-4 text-gray-500 hover:text-gray-700"
                    data-target="cp-new-password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres, mayúscula, minúscula y carácter especial.</p>
        </div>

        <!-- Indicador de fortaleza -->
        <div id="cp-strength-container" class="hidden space-y-1">
            <div class="flex items-center gap-2">
                <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div id="cp-strength-fill" class="h-full w-0 transition-all duration-300 rounded-full"></div>
                </div>
                <span id="cp-strength-text" class="text-xs font-semibold w-20 text-right"></span>
            </div>
            <ul class="text-xs space-y-1 mt-1">
                <li id="cp-req-length" class="text-gray-400 flex items-center gap-1">
                    <i class="bi bi-circle-fill text-xs"></i>Mínimo 8 caracteres
                </li>
                <li id="cp-req-uppercase" class="text-gray-400 flex items-center gap-1">
                    <i class="bi bi-circle-fill text-xs"></i>Una letra mayúscula
                </li>
                <li id="cp-req-lowercase" class="text-gray-400 flex items-center gap-1">
                    <i class="bi bi-circle-fill text-xs"></i>Una letra minúscula
                </li>
                <li id="cp-req-special" class="text-gray-400 flex items-center gap-1">
                    <i class="bi bi-circle-fill text-xs"></i>Un carácter especial (!@#$...)
                </li>
            </ul>
        </div>

        <!-- Confirmar contraseña -->
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Confirmar nueva contraseña</label>
            <div class="relative">
                <input type="password" id="cp-confirm-password" name="confirm_password" required
                    placeholder="Repite la nueva contraseña"
                    class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition">
                <button type="button" class="cp-toggle absolute inset-y-0 right-0 px-4 text-gray-500 hover:text-gray-700"
                    data-target="cp-confirm-password">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <!-- Mensaje de feedback -->
        <div id="cp-message" class="hidden p-3 rounded-lg text-sm font-medium"></div>

        <!-- Botón submit -->
        <button type="submit" id="cp-submit"
            class="w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white font-semibold py-3 rounded-lg
                   hover:shadow-lg hover:from-purple-600 hover:to-blue-600 transition
                   disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
            <i class="bi bi-check-circle"></i>
            Cambiar contraseña y continuar
        </button>
    </form>
</div>
