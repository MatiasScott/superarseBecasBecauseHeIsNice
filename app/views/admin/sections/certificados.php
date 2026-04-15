<section class="bg-white rounded-2xl shadow-xl p-5 md:p-6 space-y-5">
    <h2 class="text-2xl font-bold text-gray-800">Módulo de certificados</h2>

    <!-- ESTADÍSTICAS POR NIVEL -->
    <div class="rounded-2xl border border-gray-200 p-5 space-y-4">
        <h3 class="text-xl font-bold text-gray-800">Certificados por nivel</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach (($niveles ?? []) as $nivel) : ?>
                <div class="rounded-xl p-4 bg-gray-50 border border-gray-200 text-center">
                    <p class="text-sm font-semibold text-gray-600">Nivel <?= h($nivel) ?></p>
                    <p class="text-2xl font-extrabold text-gray-800"><?= h($conteoNiveles[$nivel] ?? 0) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CARGA MASIVA DE CERTIFICADOS -->
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 space-y-4">
        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="bi bi-cloud-upload"></i> Carga masiva de certificados
        </h3>
        <p class="text-sm text-gray-600">Especifica la carpeta donde se encuentran los archivos PDF y selecciona el nivel de los certificados.</p>

        <form id="bulkUploadForm" class="space-y-4">
            <input type="hidden" name="_csrf" value="<?= h($csrfToken ?? '') ?>">

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="carpeta_ruta" class="block text-sm font-semibold text-gray-700 mb-1">Ruta de la carpeta</label>
                    <input id="carpeta_ruta" name="carpeta_ruta" type="text" required placeholder="Ej: D:\CERTIFICADOS FIRMADOS\Certificados"
                        class="w-full border rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Ruta absoluta o relativa al servidor</p>
                </div>

                <div>
                    <label for="nivel_certificado" class="block text-sm font-semibold text-gray-700 mb-1">Nivel</label>
                    <select id="nivel_certificado" name="nivel" required
                        class="w-full border rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Selecciona un nivel --</option>
                        <?php foreach (($niveles ?? []) as $nivel) : ?>
                            <option value="<?= h($nivel) ?>">Nivel <?= h($nivel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" id="btnIniciar" 
                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-2.5 rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="bi bi-play-fill"></i> Iniciar carga
            </button>
        </form>

        <!-- BARRA DE PROGRESO -->
        <div id="progressContainer" class="hidden space-y-2">
            <div class="flex justify-between text-sm">
                <span class="font-semibold text-gray-700">Procesando certificados...</span>
                <span id="progressText" class="text-gray-600">0 / 0</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div id="progressBar" class="bg-gradient-to-r from-blue-500 to-blue-600 h-full w-0 transition-all duration-300"></div>
            </div>
            <div id="statusLog" class="bg-gray-100 border border-gray-300 rounded-lg p-3 h-32 overflow-y-auto text-xs font-mono space-y-1">
                <p class="text-gray-600">Iniciando...</p>
            </div>
        </div>

        <!-- RESULTADO FINAL -->
        <div id="resultContainer" class="hidden p-4 rounded-lg border space-y-2">
            <h4 class="font-bold text-gray-800 flex items-center gap-2">
                <i id="resultIcon" class="bi bi-check-circle"></i>
                <span id="resultTitle">Proceso completado</span>
            </h4>
            <div id="resultStats" class="grid md:grid-cols-3 gap-3 text-sm">
                <div class="bg-green-50 border border-green-200 rounded p-2">
                    <p class="text-green-700 font-semibold">✅ Exitosos</p>
                    <p class="text-2xl font-bold text-green-900" id="successCount">0</p>
                </div>
                <div class="bg-red-50 border border-red-200 rounded p-2">
                    <p class="text-red-700 font-semibold">❌ Errores</p>
                    <p class="text-2xl font-bold text-red-900" id="errorCount">0</p>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded p-2">
                    <p class="text-gray-700 font-semibold">⏱️ Tiempo</p>
                    <p class="text-2xl font-bold text-gray-900" id="timeElapsed">0s</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ÚLTIMAS CARGAS -->
    <div class="rounded-2xl border border-gray-200 p-5 space-y-4">
        <h3 class="text-xl font-bold text-gray-800">Últimas cargas</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2 pr-3">Cédula</th>
                        <th class="py-2 pr-3">Nivel</th>
                        <th class="py-2 pr-3">Fecha</th>
                        <th class="py-2">Archivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimasCargas)) : ?>
                        <tr>
                            <td colspan="4" class="py-3 text-gray-500">No hay cargas registradas.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($ultimasCargas as $fila) : ?>
                            <tr class="border-b last:border-b-0">
                                <td class="py-2 pr-3 font-semibold text-gray-700"><?= h($fila['cedula'] ?? '') ?></td>
                                <td class="py-2 pr-3"><?= h($fila['nivel'] ?? '') ?></td>
                                <td class="py-2 pr-3"><?= h($fila['fecha_subida'] ?? '') ?></td>
                                <td class="py-2 break-all text-xs text-gray-600"><?= h($fila['ruta_archivo'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
document.getElementById('bulkUploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const carpetaRuta = document.getElementById('carpeta_ruta').value.trim();
    const nivel = document.getElementById('nivel_certificado').value;
    const csrf = document.querySelector('input[name="_csrf"]').value;
    
    if (!carpetaRuta || !nivel) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    // Mostrar progreso, esconder resultado
    document.getElementById('progressContainer').classList.remove('hidden');
    document.getElementById('resultContainer').classList.add('hidden');
    document.getElementById('btnIniciar').disabled = true;
    
    const statusLog = document.getElementById('statusLog');
    statusLog.innerHTML = '<p class="text-gray-600">Iniciando proceso...</p>';
    
    let offset = 0;
    let totalArchivos = 0;
    let exitosos = 0;
    let errores = 0;
    const startTime = Date.now();
    
    try {
        // Primera solicitud: obtener total de archivos
        const respInicio = await fetch('<?= $bulkUploadAction ?? "#" ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'init',
                carpeta_ruta: carpetaRuta,
                nivel: nivel,
                _csrf: csrf
            })
        });
        
        const dataInicio = await respInicio.json();
        if (!dataInicio.success) {
            throw new Error(dataInicio.error || 'Error al inicializar');
        }
        
        totalArchivos = dataInicio.total;
        logStatus(`📁 Se encontraron ${totalArchivos} archivos`);
        
        if (totalArchivos === 0) {
            logStatus('⚠️ No hay archivos para procesar');
            showResult(exitosos, errores, startTime);
            return;
        }
        
        // Procesar en chunks de 100
        const chunkSize = 100;
        while (offset < totalArchivos) {
            const respChunk = await fetch('<?= $bulkUploadAction ?? "#" ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'process',
                    carpeta_ruta: carpetaRuta,
                    nivel: nivel,
                    offset: offset,
                    chunk_size: chunkSize,
                    _csrf: csrf
                })
            });
            
            const dataChunk = await respChunk.json();
            if (!dataChunk.success) {
                throw new Error(dataChunk.error || 'Error al procesar chunk');
            }
            
            exitosos += dataChunk.exitosos || 0;
            errores += dataChunk.errores || 0;
            
            // Actualizar progreso
            offset += chunkSize;
            const porcentaje = Math.min(100, (offset / totalArchivos) * 100);
            document.getElementById('progressBar').style.width = porcentaje + '%';
            document.getElementById('progressText').textContent = `${offset} / ${totalArchivos}`;
            
            // Log de errores si los hay
            if (dataChunk.mensajes && dataChunk.mensajes.length > 0) {
                dataChunk.mensajes.forEach(msg => logStatus(msg));
            }
        }
        
        showResult(exitosos, errores, startTime);
        
    } catch (error) {
        logStatus('❌ ' + error.message);
        showResult(exitosos, errores, startTime);
    } finally {
        document.getElementById('btnIniciar').disabled = false;
    }
});

function logStatus(message) {
    const statusLog = document.getElementById('statusLog');
    const p = document.createElement('p');
    p.textContent = message;
    p.className = 'text-gray-700';
    statusLog.appendChild(p);
    statusLog.scrollTop = statusLog.scrollHeight;
}

function showResult(exitosos, errores, startTime) {
    const elapsed = Math.round((Date.now() - startTime) / 1000);
    
    document.getElementById('successCount').textContent = exitosos;
    document.getElementById('errorCount').textContent = errores;
    document.getElementById('timeElapsed').textContent = elapsed + 's';
    
    const resultContainer = document.getElementById('resultContainer');
    if (errores === 0) {
        resultContainer.className = 'hidden p-4 rounded-lg border space-y-2 bg-green-50 border-green-200';
        document.getElementById('resultIcon').className = 'bi bi-check-circle text-green-600';
        document.getElementById('resultTitle').textContent = '✅ Proceso completado exitosamente';
    } else {
        resultContainer.className = 'hidden p-4 rounded-lg border space-y-2 bg-yellow-50 border-yellow-200';
        document.getElementById('resultIcon').className = 'bi bi-exclamation-triangle text-yellow-600';
        document.getElementById('resultTitle').textContent = '⚠️ Proceso completado con errores';
    }
    resultContainer.classList.remove('hidden');
}
</script>
