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
    <title>Registro Exitoso - Instituto Superior Tecnológico Superarse</title>
    <!-- Carga Tailwind CSS para estilos rápidos y responsivos -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Íconos de Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Tu archivo de estilos personalizado, si aún lo usas -->
    <link rel="stylesheet" href="<?= h($assetCssPath ?? '') ?>">
</head>

<body class="min-h-screen bg-gray-50 flex flex-col">

    <div class="bg-white rounded-2xl shadow-2xl w-[90%] mx-auto my-8 overflow-visible flex flex-col">
        <div class="flex justify-end p-4 md:p-6 bg-white border-b border-gray-100">
            <form action="<?= h($logoutUrl ?? '#') ?>" method="POST" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken ?? '') ?>">
                <button type="submit"
                    class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-all duration-300 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-red-300">
                    <i class="bi bi-box-arrow-right"></i>
                    Cerrar sesión
                </button>
            </form>
        </div>

        <!-- Header con gradiente -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 p-8 md:p-12 text-center">
            <h2 class="text-4xl md:text-5xl font-extrabold text-white mb-2">
                ¡Registro completado! 🎉
            </h2>
            <p class="text-green-100 text-lg">Tu acceso a la plataforma está listo</p>
        </div>

        <!-- Contenido principal -->
        <div class="p-8 md:p-12 space-y-8">
            <!-- Sección de bienvenida -->
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-6 border-l-4 border-blue-600">
                <p class="text-gray-800 text-lg font-semibold mb-4">
                    🎓 Tu usuario para ingresar al Aula Virtual:
                </p>
                <p class="text-2xl font-bold text-blue-700 bg-white inline-block px-4 py-2 rounded-lg">
                    <?= h($data['cedula']) ?>
                </p>
                <p class="text-sm text-gray-600 italic mt-4 pt-4 border-t border-blue-200">
                    💡 Por seguridad, si necesitas restablecer tu contraseña de Moodle, solicita soporte al área académica.
                </p>
            </div>

            <!-- Grupo -->
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border-l-4 border-purple-600">
                <p class="text-gray-800 text-lg font-semibold mb-3">
                    👥 Tu grupo:
                </p>
                <p class="text-xl font-bold text-purple-700">
                    <?= h($data['grupo']) ?>
                </p>
            </div>

            <!-- Botones de acceso rápido -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="<?= h($data['moodle_link']) ?>" target="_blank"
                    class="group flex items-center justify-center gap-3 bg-gradient-to-br from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold py-4 px-6 rounded-xl shadow-lg transform transition-all duration-300 hover:shadow-xl hover:scale-105">
                    <i class="bi bi-mortarboard-fill text-2xl group-hover:scale-110 transition-transform"></i>
                    <span>Entrar a Moodle</span>
                </a>

                <a href="<?= h($data['whatsapp_link']) ?>" target="_blank"
                    class="group flex items-center justify-center gap-3 bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg transform transition-all duration-300 hover:shadow-xl hover:scale-105">
                    <i class="bi bi-whatsapp text-2xl group-hover:scale-110 transition-transform"></i>
                    <span>Entrar a WhatsApp</span>
                </a>
            </div>

            <!-- Separador -->
            <div class="h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>

            <!-- Sección de detalles de clases -->
            <div class="space-y-4">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                    <i class="bi bi-calendar3 text-blue-600"></i>
                    Detalles de tus clases
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4 border-l-4 border-blue-600">
                        <p class="text-gray-600 text-sm font-semibold mb-1">📅 Fecha de acceso a aulas:</p>
                        <p class="text-gray-800 font-bold text-lg"><?= h($data['fecha_acceso_aulas']) ?></p>
                    </div>

                    <div class="bg-purple-50 rounded-lg p-4 border-l-4 border-purple-600">
                        <p class="text-gray-600 text-sm font-semibold mb-1">🎥 Fecha de inicio en Zoom:</p>
                        <p class="text-gray-800 font-bold text-lg"><?= h($data['fecha_inicio_zoom']) ?></p>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4 border-l-4 border-green-600">
                        <p class="text-gray-600 text-sm font-semibold mb-1">⏰ Horario de clases:</p>
                        <p class="text-gray-800 font-bold text-lg"><?= h($data['hora_clases']) ?></p>
                    </div>

                    <div class="bg-orange-50 rounded-lg p-4 border-l-4 border-orange-600">
                        <p class="text-gray-600 text-sm font-semibold mb-1">👨‍🏫 Profesor/a asignado/a:</p>
                        <p class="text-gray-800 font-bold text-lg"><?= h($data['profesor']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Separador -->
            <div class="h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>

            <!-- Sección de descarga de certificados -->
            <div class="space-y-6">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                    <i class="bi bi-award-fill text-yellow-500 text-3xl"></i>
                    Descarga tus certificados
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php
                    foreach ($certificadosVista as $certificado) {
                        $hay_certificado = (bool) $certificado['disponible'];
                        $nivel = $certificado['nivel'];
                        $urlDescarga = $certificado['url'];

                        if ($hay_certificado) {
                            echo "<a href='" . h($urlDescarga) . "' target='_blank' rel='noopener noreferrer'
                                class='group bg-gradient-to-br from-teal-50 to-cyan-50 border-2 border-teal-300 hover:border-teal-500 rounded-lg p-6 shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1'>";
                            echo "<div class='flex items-center justify-between mb-3'>";
                            echo "<p class='text-xl font-bold text-teal-700'>Nivel " . h($nivel) . "</p>";
                            echo "<i class='bi bi-check-circle-fill text-green-500 text-2xl group-hover:scale-110 transition-transform'></i>";
                            echo "</div>";
                            echo "<button type='button' class='w-full bg-gradient-to-r from-teal-500 to-cyan-500 hover:from-teal-600 hover:to-cyan-600 text-white font-bold py-3 px-4 rounded-lg shadow-md transition-all duration-300 flex items-center justify-center gap-2 group-hover:shadow-lg'>";
                            echo "<i class='bi bi-download text-lg group-hover:scale-110 transition-transform'></i>";
                            echo "Descargar Certificado";
                            echo "</button>";
                            echo "</a>";
                        } else {
                            echo "<div class='bg-gray-100 border-2 border-gray-300 rounded-lg p-6 opacity-60'>";
                            echo "<div class='flex items-center justify-between mb-3'>";
                            echo "<p class='text-xl font-bold text-gray-500'>Nivel " . h($nivel) . "</p>";
                            echo "<i class='bi bi-x-circle-fill text-red-400 text-2xl'></i>";
                            echo "</div>";
                            echo "<button type='button' disabled class='w-full bg-gray-300 text-gray-500 font-bold py-3 px-4 rounded-lg cursor-not-allowed flex items-center justify-center gap-2'>";
                            echo "<i class='bi bi-lock-fill'></i>";
                            echo "No Disponible";
                            echo "</button>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- Separador -->
            <div class="h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>

            <!-- Sección de recursos y videos educativos -->
            <div class="space-y-8">
                <!-- Beca en Inglés -->
                <div class="space-y-4">
                    <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="bi bi-film text-red-600"></i>
                        Beca en Inglés
                    </h3>
                    <div class="bg-gray-100 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow max-w-md mx-auto">
                        <video src="<?= h($videoBecaInglesUrl ?? '') ?>"
                            class="w-full h-auto"
                            controls preload="metadata">
                            Tu navegador no soporta el elemento de video.
                        </video>
                    </div>
                </div>

                <!-- Sitio web -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 shadow-lg">
                    <p class="text-white text-center font-semibold mb-4">📚 Para más información visita nuestra página web</p>
                    <a href="https://superarse.edu.ec/" target="_blank"
                        class="group flex items-center justify-center gap-2 bg-white hover:bg-gray-100 text-blue-700 font-bold py-3 px-6 rounded-lg transition-all duration-300">
                        <i class="bi bi-globe text-lg group-hover:rotate-12 transition-transform"></i>
                        Instituto Superarse
                    </a>
                </div>

                <!-- TikTok -->
                <div class="bg-gradient-to-r from-black to-gray-800 rounded-xl p-6 shadow-lg">
                    <p class="text-white text-center font-semibold mb-4">🎵 Síguenos en TikTok para más contenido</p>
                    <a href="https://www.tiktok.com/@becasuperarse" target="_blank"
                        class="group flex items-center justify-center gap-2 bg-white hover:bg-gray-100 text-black font-bold py-3 px-6 rounded-lg transition-all duration-300">
                        <i class="bi bi-tiktok text-lg group-hover:scale-110 transition-transform"></i>
                        @becasuperarse
                    </a>
                </div>

                <!-- Tutorial Moodle -->
                <div class="space-y-4">
                    <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="bi bi-mortarboard-fill text-purple-600"></i>
                        Tutorial de ingreso a Moodle
                    </h3>
                    <div class="bg-gray-100 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow max-w-md mx-auto">
                        <video src="<?= h($videoMoodleUrl ?? '') ?>"
                            class="w-full h-auto"
                            controls preload="metadata">
                            Tu navegador no soporta el elemento de video.
                        </video>
                    </div>
                </div>

                <!-- Tutorial Zoom -->
                <div class="space-y-4">
                    <h3 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                        <i class="bi bi-camera-video-fill text-blue-600"></i>
                        Tutorial de ingreso a Zoom
                    </h3>
                    <div class="bg-gray-100 rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow max-w-md mx-auto">
                        <video src="<?= h($videoZoomUrl ?? '') ?>"
                            class="w-full h-auto"
                            controls preload="metadata">
                            Tu navegador no soporta el elemento de video.
                        </video>
                    </div>
                </div>
            </div>

            <!-- Footer de cierre -->
            <div class="bg-gradient-to-r from-green-100 to-blue-100 border-l-4 border-green-600 rounded-lg p-6 text-center">
                <p class="text-gray-800 font-semibold text-lg">
                    ✨ ¡Ya estás listo para comenzar! ✨
                </p>
                <p class="text-gray-700 mt-2">
                    Si tienes dudas o necesitas ayuda, contacta al área de soporte.
                </p>
            </div>
        </div>
    </div>
</body>

</html>