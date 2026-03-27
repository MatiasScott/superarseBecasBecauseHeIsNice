<?php
function h($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
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
    <link rel="stylesheet" href="/ISuperarse/public/assets/css/styles.css">
</head>

<body class="min-h-screen flex flex-col p-4 bg-gray-50">

    <div class="container bg-white p-8 rounded-xl shadow-lg max-w-4xl w-full text-center space-y-6 md:space-y-8">
        <h2 class="text-3xl md:text-4xl font-extrabold text-green-700 mb-4 animate-fade-in-down">
            ¡Registro completado exitosamente! 🎉
        </h2>

        <!-- Sección de enlaces importantes (WhatsApp y Moodle) -->
        <div class="space-y-4">
            <p class="text-gray-700 text-base leading-relaxed">
                Tu usuario para ingresar al Aula Virtual es: <strong class="text-blue-700"><?= h($data['cedula']) ?></strong>
            </p>
            <p class="text-gray-700 text-base leading-relaxed">
                Tu contraseña es: <strong class="text-blue-700"><?= h($data['contrasenia']) ?></strong>
            </p>
            <p class="text-sm text-gray-500 italic">
                <em>En algunas ocasiones, el sistema solicitará que cambies tu contraseña por motivos de seguridad.
                    Si no es el caso, puedes continuar utilizando la misma contraseña que se te proporcionó inicialmente.</em>
            </p>
            <p class="text-gray-700 text-base leading-relaxed">
                Tu grupo es: <strong class="text-blue-700"><?= h($data['grupo']) ?></strong>
            </p>
            <div class="flex flex-col md:flex-row items-center justify-center space-y-4 md:space-y-0 md:space-x-4">
                <a href="<?= h($data['moodle_link']) ?>" target="_blank"
                    class="inline-flex items-center justify-center bg-[#8b5cf6] hover:bg-[#7c3aed] text-white font-bold py-3 px-6 rounded-full
                  shadow-lg transform transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-[#c4b5fd]">
                    <i class="bi bi-mortarboard-fill text-2xl mr-3"></i> Entrar a Moodle
                </a>

                <a href="<?= h($data['whatsapp_link']) ?>" target="_blank"
                    class="inline-flex items-center justify-center bg-[#25d366] hover:bg-[#128c7e] text-white font-bold py-3 px-6 rounded-full
                  shadow-lg transform transition-all duration-300 hover:scale-105 focus:outline-none focus:ring-4 focus:ring-[#a7f3d0]">
                    <i class="bi bi-whatsapp text-2xl mr-3"></i> Entrar a Whatsapp
                </a>
            </div>
        </div>

        <hr class="border-t-2 border-gray-200 my-8">

        <!-- Sección de detalles de clases -->
        <div class="space-y-3 text-left">
            <h3 class="text-xl font-bold text-gray-800 mb-3">Detalles de tus clases:</h3>
            <p class="text-gray-700 text-base">
                <strong>Fecha de acceso a aulas:</strong> <span class="text-blue-600"><?= h($data['fecha_acceso_aulas']) ?></span>
            </p>
            <p class="text-gray-700 text-base">
                <strong>Fecha de inicio en Zoom:</strong> <span class="text-blue-600"><?= h($data['fecha_inicio_zoom']) ?></span>
            </p>
            <p class="text-gray-700 text-base">
                <strong>Horario de clases:</strong> <span class="text-blue-600"><?= h($data['hora_clases']) ?></span>
            </p>
            <p class="text-gray-700 text-base">
                <strong>Profesor/a asignado/a:</strong> <span class="text-blue-600"><?= h($data['profesor']) ?></span>
            </p>
        </div>

        <hr class="border-t-2 border-gray-200 my-8">

        <!-- Sección de descarga de certificados mejorada -->
        <div class="space-y-6 md:space-y-8 p-6 rounded-xl shadow-inner">
            <h3 class="text-2xl md:text-3xl font-extrabold text-blue-800 text-center mb-4 flex items-center justify-center gap-3">
                <i class="bi bi-award-fill text-yellow-500 text-3xl md:text-4xl"></i>
                Descarga tus certificados aquí
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 md:gap-6">
                <?php
                foreach ($niveles_deseados as $nivel) {
                    $ruta_certificado = $certificados_encontrados[$nivel] ?? null;
                    $hay_certificado = $ruta_certificado !== null;
                    $clase_bg = $hay_certificado ? 'bg-white hover:shadow-lg' : 'bg-gray-200 cursor-not-allowed';
                    $clase_hover_transform = $hay_certificado ? 'transform hover:-translate-y-1' : '';
                    $clase_transition = 'transition-all duration-300';
                    $texto_boton = $hay_certificado ? 'Descargar Certificado' : 'No Disponible';
                    $icono_boton = $hay_certificado ? 'bi bi-download' : 'bi bi-x-circle-fill';
                    $clase_color = $hay_certificado ? 'text-gray-800' : 'text-gray-500';

                    echo "<div class='p-4 rounded-lg shadow-md {$clase_bg} {$clase_transition} {$clase_hover_transform}'>";
                    echo "<p class='{$clase_color} font-semibold text-lg mb-2'>Nivel {$nivel}</p>";

                    if ($hay_certificado) {
                        // El enlace ahora apunta al controlador, que gestiona la descarga
                        // Usa la ruta base dinámica para asegurarte de que el enlace funcione en cualquier entorno
                        $basePath = '/ISuperarse/public/'; // Ajusta esta ruta si es diferente
                        echo "<a href='{$basePath}becario/descargar?ruta=" . urlencode($ruta_certificado) . "' target='_blank' rel='noopener noreferrer' class='inline-flex items-center justify-center bg-teal-500 hover:bg-teal-600 text-white font-bold py-2 px-4 rounded-full shadow-md transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-teal-300'>";
                        echo "<i class='{$icono_boton} mr-2'></i> {$texto_boton}";
                        echo "</a>";
                    } else {
                        echo "<button class='inline-flex items-center justify-center bg-red-400 text-white font-bold py-2 px-4 rounded-full shadow-md cursor-not-allowed'>";
                        echo "<i class='{$icono_boton} mr-2'></i> {$texto_boton}";
                        echo "</button>";
                    }
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <hr class="border-t-2 border-gray-200 my-8">

        <!--<div id="modal-form-content" class="p-6 max-w-lg mx-auto rounded-xl space-y-4">

            <div class="max-w-md mx-auto bg-gray-100 p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-bold mb-4 text-center">Subir Certificado</h3>

                <form id="certificado-upload-form" action="/ISuperarse/public/becario/procesarSubida" method="POST" enctype="multipart/form-data">

                    <input type="hidden" name="cedula" value="<?= h($data['cedula']) ?>">

                    <div class="mb-4">
                        <label for="nivel" class="block text-gray-700 font-bold mb-2">Nivel:</label>
                        <select id="nivel" name="nivel" class="w-full px-3 py-2 border rounded-md" required>
                            <option value="">Seleccione un nivel</option>
                            <option value="A1">A1</option>
                            <option value="A2">A2</option>
                            <option value="B1">B1</option>
                            <option value="B2">B2</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label for="certificado" class="block text-gray-700 font-bold mb-2">Archivo del Certificado (PDF):</label>
                        <input type="file" id="certificado" name="certificado" accept=".pdf" class="w-full px-3 py-2 border rounded-md" required>
                    </div>

                    <button type="submit" class="w-full bg-blue-500 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-600 transition-colors">
                        Subir Certificado
                    </button>
                </form>
            </div>
        </div>

        <hr class="border-t-2 border-gray-200 my-8">-->

        <!-- Sección de enlaces adicionales y videos -->
        <div class="space-y-6">
            <p class="text-gray-700 text-base leading-relaxed">
                Para más información visita nuestra página web:
                <a href="https://superarse.edu.ec/" target="_blank"
                    class="inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-5 rounded-full
                          shadow-md transform transition-all duration-300 hover:scale-105 mt-2">
                    Instituto Superarse
                </a>
            </p>

            <video src="/ISuperarse/public/assets/videos/becaIngles.mp4"
                class="w-full max-w-xl mx-auto rounded-lg shadow-xl"
                controls preload="metadata">
                Tu navegador no soporta el elemento de video.
            </video>

            <p class="text-gray-700 text-base leading-relaxed">
                Síguenos en nuestro TikTok:
                <a href="https://www.tiktok.com/@becasuperarse" target="_blank"
                    class="inline-flex items-center justify-center text-black hover:text-gray-800 font-bold py-2 px-4 rounded-full
                          bg-white shadow-sm border border-gray-300 transform transition-all duration-300 hover:scale-105 mt-2">
                    <i class="bi bi-tiktok text-xl mr-2"></i> @becasuperarse
                </a>
            </p>

            <h3 class="text-xl font-bold text-gray-800 mb-3 mt-8">Tutorial de ingreso a Moodle 📚</h3>
            <video src="/ISuperarse/public/assets/videos/tutorialMoodle.mp4"
                class="w-full max-w-xl mx-auto rounded-lg shadow-xl"
                controls preload="metadata">
                Tu navegador no soporta el elemento de video.
            </video>

            <h3 class="text-xl font-bold text-gray-800 mb-3 mt-8">Tutorial de ingreso a Zoom 🖥️</h3>
            <video src="/ISuperarse/public/assets/videos/tutorialZoom.mp4"
                class="w-full max-w-xl mx-auto rounded-lg shadow-xl"
                controls preload="metadata">
                Tu navegador no soporta el elemento de video.
            </video>
        </div>
    </div>
</body>

</html>