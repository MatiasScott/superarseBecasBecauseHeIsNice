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

    <link rel="stylesheet" href="/assets/css/styles.css">

</head>

<body class="min-h-screen flex flex-col">

    <main class="flex-1 flex flex-col items-center justify-center">
        <header class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-extrabold text-blue-700 mb-2">
                Instituto Superior Tecnológico Superarse
            </h1>
            <h3 class="text-xl md:text-2xl font-semibold text-gray-700">
                Programa de becas de Inglés "Because He Is Nice"
            </h3>
        </header>

        <div class="container bg-white p-8 rounded-xl shadow-lg max-w-2xl w-full text-center space-y-6">

            <h2 class="text-2xl font-bold text-gray-800 mb-4">Estimado/a estudiante: 👋</h2>

            <p class="text-gray-700 leading-relaxed">

                El Instituto Superior Tecnológico Superarse te da la más cordial bienvenida al programa de beca "Because He Is Nice".

            </p>

            <p class="text-gray-700 leading-relaxed">

                Para iniciar, completa tu registro y únete al grupo oficial de WhatsApp, donde compartiremos información clave durante el programa.

            </p>

            <h2 class="text-2xl font-bold text-gray-800 mt-8 mb-4">Buscar por cédula 🔎</h2>

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

                <button type="submit" name="buscar"

                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg

                focus:outline-none focus:shadow-outline transition-all duration-200 transform hover:scale-105 w-full md:w-auto">

                    Buscar

                </button>

            </form>

            <p class="text-gray-700 leading-relaxed mt-6">

                Recibirás en tu correo tu acceso a Moodle, donde vivirás una experiencia enriquecedora de aprendizaje en inglés.

            </p>

            <p class="text-gray-700 leading-relaxed mt-6">

                ¿Tienes inconvenientes? Contáctanos:

                <a href="https://wa.me/593992531588" class="text-green-500 hover:text-green-600 font-medium transition-colors duration-200">

                    <i class="bi bi-whatsapp mr-1"></i> 0992531588

                </a>

                o

                <a href="mailto:ingles@superarse.edu.ec" class="text-blue-500 hover:text-blue-600 font-medium transition-colors duration-200">

                    <i class="bi bi-envelope-fill mr-1"></i> ingles@superarse.edu.ec

                </a>

            </p>
        </div>

    </main>

    <!-- Modal para mostrar los formularios o mensajes de error del backend -->

    <div id="modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden p-4">

        <div class="modal-content bg-white rounded-xl shadow-2xl p-8 max-w-lg w-full relative transform transition-all duration-300 scale-95 opacity-0">

            <span class="close absolute top-4 right-6 text-gray-500 text-3xl font-bold cursor-pointer hover:text-gray-800">&times;</span>

            <div id="modal-body" class="mt-4">

                <!-- Aquí se cargará el contenido dinámicamente (becario_form.php o becario_not_found.php) -->

            </div>

        </div>

    </div>

    <footer class="footer-container text-center text-gray-600 text-sm">
        <p class="mb-0">&copy; 2025 Instituto Superarse. Todos los derechos reservados.</p>
    </footer>

    <script src="/assets/js/home.js"></script>

</body>

</html>