<?php
function h($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar contrasena admin - Instituto Superarse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= h($assetCssPath ?? '') ?>">
</head>

<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4" data-base-path="<?= h($basePath ?? '') ?>">
    <div class="bg-white rounded-2xl shadow-2xl w-[90%] max-w-2xl p-8 md:p-10 space-y-6">
        <div class="text-center space-y-2">
            <h1 class="text-3xl font-extrabold text-blue-700">Cambiar contrasena admin</h1>
            <p class="text-gray-600">Usuario: <strong><?= h($adminUsuario ?? '') ?></strong></p>
            <?php if (!empty($isFirstLogin)) : ?>
                <p class="text-sm rounded-lg border border-amber-200 bg-amber-50 text-amber-800 px-3 py-2 inline-block">
                    Primer inicio detectado: debes cambiar tu contrasena para continuar.
                </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($errorMessage)) : ?>
            <div class="rounded-xl p-4 border bg-red-50 border-red-200 text-red-700">
                <?= h($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form id="admin_change_password_form" method="POST" action="" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">

            <div>
                <label for="current_password" class="block text-sm font-bold text-gray-700 mb-2">Contrasena actual</label>
                <input id="current_password" name="current_password" type="password" required
                    class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Contrasena actual">
            </div>

            <div>
                <label for="new_password" class="block text-sm font-bold text-gray-700 mb-2">Nueva contrasena</label>
                <input id="new_password" name="new_password" type="password" required
                    class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Nueva contrasena">
                <p class="text-xs text-gray-500 mt-2">Minimo 8 caracteres, una mayuscula, una minuscula y un caracter especial.</p>

                <div id="password_requirements" class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <p class="text-xs font-semibold text-gray-700 mb-2">Estado de requisitos</p>
                    <p id="password_progress" class="text-xs text-gray-600 mb-2">Cumples 0 de 4 requisitos.</p>
                    <ul class="space-y-1 text-xs">
                        <li id="req_length" class="text-gray-400 flex items-center gap-2">
                            <i class="bi bi-circle-fill"></i>
                            Minimo 8 caracteres
                        </li>
                        <li id="req_uppercase" class="text-gray-400 flex items-center gap-2">
                            <i class="bi bi-circle-fill"></i>
                            Una mayuscula
                        </li>
                        <li id="req_lowercase" class="text-gray-400 flex items-center gap-2">
                            <i class="bi bi-circle-fill"></i>
                            Una minuscula
                        </li>
                        <li id="req_special" class="text-gray-400 flex items-center gap-2">
                            <i class="bi bi-circle-fill"></i>
                            Un caracter especial
                        </li>
                    </ul>
                </div>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-bold text-gray-700 mb-2">Confirmar nueva contrasena</label>
                <input id="confirm_password" name="confirm_password" type="password" required
                    class="w-full border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Repite la nueva contrasena">

                <p id="confirm_feedback" class="hidden text-xs mt-2"></p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button id="submit_button" type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-200 shadow-md">
                    Guardar nueva contrasena
                </button>

                <button type="button" onclick="window.history.back()"
                    class="w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 transition-all duration-200">
                    Volver
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const form = document.getElementById('admin_change_password_form');
            const submitButton = document.getElementById('submit_button');
            const requirementsBox = document.getElementById('password_requirements');
            const passwordProgress = document.getElementById('password_progress');
            const confirmFeedback = document.getElementById('confirm_feedback');

            if (!newPasswordInput || !confirmPasswordInput || !form || !submitButton || !requirementsBox || !passwordProgress || !confirmFeedback) {
                return;
            }

            const requirementMap = {
                length: document.getElementById('req_length'),
                uppercase: document.getElementById('req_uppercase'),
                lowercase: document.getElementById('req_lowercase'),
                special: document.getElementById('req_special'),
            };

            function paintRequirement(element, ok) {
                if (!element) return;
                const icon = element.querySelector('i');

                if (ok) {
                    element.classList.remove('text-gray-400');
                    element.classList.add('text-green-600');
                    if (icon) {
                        icon.classList.remove('bi-circle-fill');
                        icon.classList.add('bi-check-circle-fill');
                    }
                    return;
                }

                element.classList.remove('text-green-600');
                element.classList.add('text-gray-400');
                if (icon) {
                    icon.classList.remove('bi-check-circle-fill');
                    icon.classList.add('bi-circle-fill');
                }
            }

            function getPasswordChecks(password) {
                return {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    special: /[!@#$%^&*()_+\-=\[\]{};:'\",.<>?/\\|`~]/.test(password),
                };
            }

            function validateNewPassword() {
                const password = newPasswordInput.value || '';

                const checks = getPasswordChecks(password);
                const totalCompleted = Object.values(checks).filter(Boolean).length;

                passwordProgress.textContent = 'Cumples ' + totalCompleted + ' de 4 requisitos.';

                paintRequirement(requirementMap.length, checks.length);
                paintRequirement(requirementMap.uppercase, checks.uppercase);
                paintRequirement(requirementMap.lowercase, checks.lowercase);
                paintRequirement(requirementMap.special, checks.special);

                validateConfirmation();
                toggleSubmit();
            }

            function validateConfirmation() {
                const newPassword = newPasswordInput.value || '';
                const confirmPassword = confirmPasswordInput.value || '';

                if (confirmPassword.length === 0) {
                    confirmFeedback.classList.add('hidden');
                    confirmFeedback.textContent = '';
                    return false;
                }

                confirmFeedback.classList.remove('hidden', 'text-red-600', 'text-green-600');
                if (newPassword === confirmPassword) {
                    confirmFeedback.classList.add('text-green-600');
                    confirmFeedback.textContent = 'Las contrasenas coinciden.';
                    return true;
                }

                confirmFeedback.classList.add('text-red-600');
                confirmFeedback.textContent = 'Las contrasenas no coinciden.';
                return false;
            }

            function isPasswordValid() {
                const checks = getPasswordChecks(newPasswordInput.value || '');
                return checks.length && checks.uppercase && checks.lowercase && checks.special;
            }

            function toggleSubmit() {
                const canSubmit = isPasswordValid() && validateConfirmation();
                submitButton.disabled = !canSubmit;
                submitButton.classList.toggle('opacity-50', !canSubmit);
                submitButton.classList.toggle('cursor-not-allowed', !canSubmit);
            }

            form.addEventListener('submit', function (event) {
                const passwordOk = isPasswordValid();
                const confirmationOk = validateConfirmation();

                if (!passwordOk || !confirmationOk) {
                    event.preventDefault();
                    confirmFeedback.classList.remove('hidden', 'text-green-600');
                    confirmFeedback.classList.add('text-red-600');
                    if (!passwordOk) {
                        confirmFeedback.textContent = 'La nueva contrasena no cumple todos los requisitos.';
                    } else {
                        confirmFeedback.textContent = 'Las contrasenas no coinciden.';
                    }
                }
            });

            newPasswordInput.addEventListener('input', validateNewPassword);
            confirmPasswordInput.addEventListener('input', function () {
                validateConfirmation();
                toggleSubmit();
            });

            toggleSubmit();
        })();
    </script>
</body>

</html>
