<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registro - Instituto Superior Tecnológico Superarse</title>

    <!-- Carga Tailwind CSS para estilos rápidos y responsivos -->

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Configuración para usar la fuente Inter (opcional, pero mejora la estética) -->

    <!-- Íconos de Bootstrap (útiles para elementos visuales) -->

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- Tu archivo de estilos personalizado, si aún lo usas para estilos específicos -->

    <link rel="stylesheet" href="<?= htmlspecialchars($assetCssPath ?? '') ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">

</head>

<body class="min-h-screen bg-gray-50 flex flex-col" data-base-path="<?= htmlspecialchars($basePath ?? '') ?>">

    <main class="flex-1 flex flex-col items-center justify-center p-4 md:p-6">
        <header class="text-center mb-6 md:mb-8">
            <h1 class="text-4xl md:text-5xl font-extrabold text-blue-700 mb-2 tracking-tight">
                Instituto Superior Tecnológico Superarse
            </h1>
            <h3 class="text-xl md:text-2xl font-semibold text-gray-700">
                Programa de becas de Inglés "Because He Is Nice"
            </h3>
        </header>

        <div class="bg-white p-8 md:p-10 rounded-2xl shadow-2xl w-[90%] max-w-4xl text-center space-y-6">

            <h2 class="text-2xl font-bold text-gray-800 mb-4">Estimado/a estudiante: 👋</h2>

            <p class="text-gray-700 leading-relaxed">

                El Instituto Superior Tecnológico Superarse te da la más cordial bienvenida al programa de beca "Because He Is Nice".

            </p>

            <p class="text-gray-700 leading-relaxed">

                Para iniciar, completa tu registro y únete al grupo oficial de WhatsApp, donde compartiremos información clave durante el programa.

            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Inicia sesión 🔐</h2>

            <form id="buscar-form" class="space-y-4">

                <div>

                    <label for="cedula" class="block text-gray-700 text-sm font-bold mb-2">Cédula:</label>

                    <input type="text" name="cedula" id="cedula" required

                        class="shadow appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight

                    focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"

                        placeholder="Ingresa tu cédula de 10 dígitos">

                    <!-- Div para mostrar mensajes de error de validación de cédula -->

                    <div id="cedula-error-message" class="text-red-500 text-sm mt-2 hidden"></div>

                </div>

                <div>

                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña:</label>

                    <div class="relative">
                        <input type="password" name="password" id="password" required

                            class="shadow appearance-none border rounded-lg w-full py-3 px-4 pr-12 text-gray-700 leading-tight

                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"

                            placeholder="Ingresa tu contraseña de acceso">

                        <button type="button" id="toggle-password"
                            class="absolute inset-y-0 right-0 px-4 text-gray-500 hover:text-gray-700 transition-colors duration-200">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>

                    <div id="password-error-message" class="text-red-500 text-sm mt-2 hidden"></div>

                    <p class="text-sm text-gray-500 mt-2">
                        Si es tu primer ingreso, tu contraseña inicial es tu misma cédula.
                    </p>

                </div>

                <button type="submit" name="buscar"
                    class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-3 px-6 rounded-lg
                focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-200 transform hover:scale-105 w-full md:w-auto shadow-md">

                    Ingresar

                </button>

            </form>

            <p class="text-gray-700 leading-relaxed mt-6">

                ¿Tienes inconvenientes? Contáctanos:

                <a href="mailto:ingles@superarse.edu.ec" class="text-blue-500 hover:text-blue-600 font-medium transition-colors duration-200">

                    <i class="bi bi-envelope-fill mr-1"></i> ingles@superarse.edu.ec

                </a>

            </p>
        </div>

    </main>

    <!-- Modal para mostrar los formularios o mensajes de error del backend -->

    <div id="modal" class="fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center z-50 hidden p-4">

        <div class="modal-content bg-white rounded-2xl shadow-2xl p-8 max-w-lg w-full relative transform transition-all duration-300 scale-95 opacity-0 border border-gray-100">

            <span class="close absolute top-4 right-6 text-gray-500 text-3xl font-bold cursor-pointer hover:text-gray-800">&times;</span>

            <div id="modal-body" class="mt-4">

                <!-- Aquí se cargará el contenido dinámicamente (becario_form.php o becario_not_found.php) -->

            </div>

        </div>

    </div>

    <footer class="footer-container text-center text-gray-600 text-sm py-4">
        <p class="mb-0">&copy; 2025 Instituto Superarse. Todos los derechos reservados.</p>
    </footer>

    <script src="<?= htmlspecialchars($homeJsPath ?? '') ?>"></script>

</body>

</html>