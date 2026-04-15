<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <title>Cambiar Contraseña</title>
    <link href="https://cdn.tailwindcss.com" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= htmlspecialchars($assetCssPath ?? '') ?>">
</head>
<body class="bg-gradient-to-br from-purple-50 to-blue-50 min-h-screen flex items-center justify-center p-4" data-base-path="<?= htmlspecialchars($basePath ?? '') ?>">
    <div class="w-full max-w-md">
        <!-- Card Principal -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full mb-4">
                    <i class="bi bi-key text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Cambiar Contraseña</h1>
                <p class="text-gray-600 mt-2">Actualiza tu contraseña de forma segura</p>
            </div>

            <!-- Formulario -->
            <form id="changePasswordForm" class="space-y-6">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="cedula" value="<?= htmlspecialchars($cedula ?? '') ?>">

                <!-- Campo: Contraseña actual -->
                <div>
                    <label for="currentPassword" class="block text-sm font-semibold text-gray-700 mb-2">
                        Contraseña actual
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="currentPassword"
                            name="current_password"
                            required
                            placeholder="Ingresa tu contraseña actual"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        >
                        <button
                            type="button"
                            id="toggleCurrentPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-800"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Campo: Nueva Contraseña -->
                <div>
                    <label for="newPassword" class="block text-sm font-semibold text-gray-700 mb-2">
                        Nueva Contraseña
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="newPassword" 
                            name="new_password" 
                            required
                            placeholder="Ingresa tu nueva contraseña"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        >
                        <button 
                            type="button" 
                            id="togglePassword" 
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-800"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 mt-2">
                        <strong>Requisitos:</strong> Mínimo 8 caracteres, mayúscula, minúscula y carácter especial
                    </p>
                </div>

                <!-- Indicador de Fortaleza de Contraseña -->
                <div id="passwordStrengthContainer" class="hidden">
                    <div class="flex items-center gap-2 mb-2">
                        <div id="strengthBar" class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div id="strengthFill" class="h-full w-0 transition-all duration-300"></div>
                        </div>
                        <span id="strengthText" class="text-xs font-semibold"></span>
                    </div>
                    <ul id="requirementsList" class="text-xs space-y-1">
                        <li id="req-length" class="text-gray-400">
                            <i class="bi bi-circle-fill text-xs mr-1"></i>Mínimo 8 caracteres
                        </li>
                        <li id="req-uppercase" class="text-gray-400">
                            <i class="bi bi-circle-fill text-xs mr-1"></i>Una letra mayúscula
                        </li>
                        <li id="req-lowercase" class="text-gray-400">
                            <i class="bi bi-circle-fill text-xs mr-1"></i>Una letra minúscula
                        </li>
                        <li id="req-special" class="text-gray-400">
                            <i class="bi bi-circle-fill text-xs mr-1"></i>Un carácter especial
                        </li>
                    </ul>
                </div>

                <!-- Confirmar Contraseña -->
                <div>
                    <label for="confirmPassword" class="block text-sm font-semibold text-gray-700 mb-2">
                        Confirmar Contraseña
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="confirmPassword" 
                            name="confirm_password" 
                            required
                            placeholder="Confirma tu nueva contraseña"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        >
                        <button 
                            type="button" 
                            id="toggleConfirmPassword" 
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-800"
                        >
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Mensaje de Error/Éxito -->
                <div id="messageContainer" class="hidden p-4 rounded-lg text-sm font-medium"></div>

                <!-- Botones -->
                <div class="flex gap-3 pt-4">
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="flex-1 bg-gradient-to-r from-purple-500 to-blue-500 text-white font-semibold py-3 rounded-lg hover:shadow-lg hover:from-purple-600 hover:to-blue-600 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <i class="bi bi-check-circle"></i>
                        Actualizar Contraseña
                    </button>
                    <a 
                        href="javascript:window.history.back()" 
                        class="flex-1 bg-gray-200 text-gray-800 font-semibold py-3 rounded-lg hover:bg-gray-300 transition flex items-center justify-center gap-2"
                    >
                        <i class="bi bi-arrow-left"></i>
                        Cancelar
                    </a>
                </div>
            </form>

            <!-- Footer -->
            <p class="text-center text-xs text-gray-600 mt-6">
                Tu contraseña está protegida y encriptada con estándares de seguridad modernos.
            </p>
        </div>
    </div>

    <script>
        // Toggle de visibilidad de contraseña
        document.getElementById('toggleCurrentPassword').addEventListener('click', function() {
            const input = document.getElementById('currentPassword');
            input.type = input.type === 'password' ? 'text' : 'password';
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });

        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('newPassword');
            input.type = input.type === 'password' ? 'text' : 'password';
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const input = document.getElementById('confirmPassword');
            input.type = input.type === 'password' ? 'text' : 'password';
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });

        // Validación en tiempo real de requisitos
        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            const container = document.getElementById('passwordStrengthContainer');
            
            if (password.length > 0) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }

            const checks = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                special: /[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/.test(password)
            };

            // Actualizar requisitos visuales
            Object.keys(checks).forEach(key => {
                const el = document.getElementById(`req-${key}`);
                if (checks[key]) {
                    el.classList.remove('text-gray-400');
                    el.classList.add('text-green-600');
                    el.querySelector('i').classList.remove('bi-circle-fill');
                    el.querySelector('i').classList.add('bi-check-circle-fill');
                } else {
                    el.classList.add('text-gray-400');
                    el.classList.remove('text-green-600');
                    el.querySelector('i').classList.add('bi-circle-fill');
                    el.querySelector('i').classList.remove('bi-check-circle-fill');
                }
            });

            // Actualizar barra de fortaleza
            const strength = Object.values(checks).filter(Boolean).length;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            const strengthLevels = ['', 'Muy débil', 'Débil', 'Buena', 'Excelente'];
            const strengthColors = ['', 'bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
            
            strengthFill.style.width = (strength * 25) + '%';
            strengthFill.className = `h-full transition-all duration-300 ${strengthColors[strength]}`;
            strengthText.textContent = strengthLevels[strength];
            strengthText.className = `text-xs font-semibold ${strengthColors[strength].replace('bg-', 'text-')}`;
        });

        // Envío del formulario
        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const submitBtn = document.getElementById('submitBtn');
            const messageContainer = document.getElementById('messageContainer');

            // Validar que las contraseñas coincidan
            if (newPassword !== confirmPassword) {
                messageContainer.classList.remove('hidden', 'bg-green-100', 'text-green-800');
                messageContainer.classList.add('bg-red-100', 'text-red-800');
                messageContainer.innerHTML = '<i class="bi bi-exclamation-circle mr-2"></i>Las contraseñas no coinciden.';
                return;
            }

            submitBtn.disabled = true;

            try {
                const formData = new FormData(this);
                const response = await fetch('<?= htmlspecialchars($changePasswordUrl ?? '/becario/change-password') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (result.success) {
                    messageContainer.classList.remove('hidden', 'bg-red-100', 'text-red-800');
                    messageContainer.classList.add('bg-green-100', 'text-green-800');
                    messageContainer.innerHTML = '<i class="bi bi-check-circle mr-2"></i>' + result.message;
                    
                    setTimeout(() => {
                        window.location.href = '<?= htmlspecialchars($registroUrl ?? '/becario/registro-exitoso') ?>';
                    }, 1500);
                } else {
                    messageContainer.classList.remove('hidden', 'bg-green-100', 'text-green-800');
                    messageContainer.classList.add('bg-red-100', 'text-red-800');
                    messageContainer.innerHTML = '<i class="bi bi-exclamation-circle mr-2"></i>' + result.error;
                }
            } catch (error) {
                messageContainer.classList.remove('hidden', 'bg-green-100', 'text-green-800');
                messageContainer.classList.add('bg-red-100', 'text-red-800');
                messageContainer.innerHTML = '<i class="bi bi-exclamation-circle mr-2"></i>Error al procesar la solicitud.';
                console.error('Error:', error);
            } finally {
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
