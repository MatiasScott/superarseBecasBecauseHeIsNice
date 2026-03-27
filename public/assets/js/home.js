// Se asegura de que el código se ejecute una vez que el DOM esté completamente cargado.
document.addEventListener('DOMContentLoaded', () => {

    // Listener para el formulario de búsqueda de cédula
    document.getElementById("buscar-form").addEventListener("submit", async function (e) {
        e.preventDefault(); // Previene el envío tradicional del formulario

        const cedulaInput = document.getElementById("cedula");
        const cedula = cedulaInput.value.trim(); // Usa trim() para limpiar espacios
        const errorMessageDiv = document.getElementById("cedula-error-message");

        // Validación de la cédula (debe tener 10 dígitos)
        if (!/^\d{10}$/.test(cedula)) {
            errorMessageDiv.textContent = "Por favor, ingresa una cédula válida de 10 dígitos.";
            errorMessageDiv.classList.remove("hidden"); // Muestra el mensaje de error
            cedulaInput.classList.add("border-red-500", "focus:ring-red-500"); // Resalta el input en rojo
            return; // Detiene la ejecución si la cédula es inválida
        } else {
            errorMessageDiv.classList.add("hidden"); // Oculta el mensaje si era válido
            cedulaInput.classList.remove("border-red-500", "focus:ring-red-500"); // Quita el resaltado rojo
        }

        // Muestra un indicador de carga
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.textContent = "Buscando...";
        submitButton.disabled = true;

        // Se construye la URL para el fetch.
        const fetchUrl = "/becario/buscar";

        try {
            // Realiza la petición Fetch al controlador PHP
            const response = await fetch(fetchUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: "cedula=" + encodeURIComponent(cedula),
            });

            // Si la respuesta no es OK, lanza un error
            if (!response.ok) {
                // Intenta parsear la respuesta como JSON
                const contentType = response.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || `Error HTTP! Estado: ${response.status}`);
                } else {
                    // Si no es JSON, lee la respuesta como texto para ver el error
                    const errorText = await response.text();
                    console.error("Respuesta del servidor no es JSON:", errorText);
                    throw new Error(`Error inesperado del servidor. Estado: ${response.status}`);
                }
            }

            // Parsea la respuesta como JSON
            const result = await response.json();

            // Muestra el resultado de la búsqueda
            const modal = document.getElementById("modal");
            const modalContent = modal.querySelector(".modal-content");
            const modalBody = document.getElementById("modal-body");

            if (result.success) {
                const formHTML = createBecarioFormHTML(result.data.becario, result.data.provincias);
                modalBody.innerHTML = formHTML;
                modal.style.display = "flex";
                setTimeout(() => {
                    modalContent.classList.remove("scale-95", "opacity-0");
                    modalContent.classList.add("scale-100", "opacity-100");
                    attachFormListeners();
                }, 10);
            } else {
                modalBody.innerHTML = `<p class="text-red-500 font-bold text-lg">${result.error}</p>`;
                modal.style.display = "flex";
                setTimeout(() => {
                    modalContent.classList.remove("scale-95", "opacity-0");
                    modalContent.classList.add("scale-100", "opacity-100");
                }, 10);
            }

        } catch (error) {
            console.error("Error en la solicitud Fetch:", error);
            const modal = document.getElementById("modal");
            const modalContent = modal.querySelector(".modal-content");
            const modalBody = document.getElementById("modal-body");

            modalBody.innerHTML = `<p class="text-red-500 font-bold text-lg">Error: ${error.message}</p>`;
            modal.style.display = "flex";
            setTimeout(() => {
                modalContent.classList.remove("scale-95", "opacity-0");
                modalContent.classList.add("scale-100", "opacity-100");
            }, 10);

        } finally {
            submitButton.textContent = "Buscar Becario";
            submitButton.disabled = false;
        }
    });

    // Event listeners para cerrar el modal
    document.addEventListener("click", function (e) {
        const modal = document.getElementById("modal");
        const modalContent = modal.querySelector(".modal-content");

        if (e.target.matches(".close") || e.target === modal) {
            modalContent.classList.remove("scale-100", "opacity-100");
            modalContent.classList.add("scale-95", "opacity-0");
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        }
    });

    // Limpiar mensaje de error al escribir
    document.getElementById("cedula").addEventListener("input", function () {
        const errorMessageDiv = document.getElementById("cedula-error-message");
        const cedulaInput = document.getElementById("cedula");
        if (!errorMessageDiv.classList.contains("hidden")) {
            errorMessageDiv.classList.add("hidden");
            cedulaInput.classList.remove("border-red-500", "focus:ring-red-500");
        }
    });

    // Función para crear el HTML del formulario del becario
    function createBecarioFormHTML(becario, provincias) {
        let provinciaOptions = provincias.map(prov =>
            `<option value="${prov}" ${becario.provincia === prov ? 'selected' : ''}>${prov}</option>`
        ).join('');

        return `
            <h2 class="text-2xl font-bold mb-4 text-center">Datos del Becario</h2>
            <form id="becario-form" action="becario/procesar" method="POST" class="space-y-4">
                <input type="hidden" name="cedula" value="${becario.cedula}">
                
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                    <input type="text" id="nombre" name="nombre" value="${becario.nombres} ${becario.apellidos}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" readonly>
                </div>
                <div>
                    <label for="cedula_display" class="block text-sm font-medium text-gray-700">Cédula</label>
                    <input type="text" id="cedula_display" name="cedula_display" value="${becario.cedula}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" readonly>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="${becario.email}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" readonly>
                </div>
                <div>
                    <label for="ciudad" class="block text-sm font-medium text-gray-700">Ciudad de Residencia</label>
                    <input type="text" id="ciudad" name="ciudad" value="${becario.ciudad}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label for="provincia" class="block text-sm font-medium text-gray-700">Provincia</label>
                    <select id="provincia" name="provincia"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        ${provinciaOptions}
                    </select>
                </div>
                <div>
                    <label for="nivel_ingles" class="block text-sm font-medium text-gray-700">Nivel de Inglés</label>
                    <input type="text" id="nivel_ingles" name="nivel_ingles" value="${becario.nivel_ingles}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" readonly>
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Actualizar Datos
                    </button>
                </div>
            </form>
        `;
    }

    // Función para adjuntar los listeners a los formularios que se cargan en el modal.
    function attachFormListeners() {
        const becarioForm = document
            .getElementById("modal-body")
            .querySelector("form[action$='becario/procesar']");
        if (becarioForm) {
            becarioForm.addEventListener("submit", handleBecarioFormSubmission);
        }
        const certificadoForm = document
            .getElementById("modal-body")
            .querySelector("form[action$='becario/procesarSubida']");
        if (certificadoForm) {
            certificadoForm.addEventListener("submit", handleCertificadoFormSubmission);
        }
    }

    function handleBecarioFormSubmission(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = "Guardando...";

        // Se ha corregido la URL para que sea relativa a la raíz del sitio
        fetch(`/index.php/becario/procesar`, {
            method: form.method,
            body: formData,
        })
        .then((response) => response.text())
        .then((html) => {
            document.getElementById("modal-body").innerHTML = html;
            attachFormListeners();
        })
        .catch((error) => {
            console.error("Error en el envío del formulario:", error);
            document.getElementById("modal-body").innerHTML = `<p class="text-red-500 font-bold text-lg">Error: ${error.message}</p>`;
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = "Continuar";
        });
    }

    function handleCertificadoFormSubmission(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = "Subiendo...";

        // Se ha corregido la URL para que sea relativa a la raíz del sitio
        fetch(`/index.php/becario/procesarSubida`, {
            method: form.method,
            body: formData,
        })
        .then((response) => {
            if (!response.ok) {
                return response.json().then((data) => {
                    throw new Error(data.error || "Error al subir el archivo.");
                });
            }
            return response.json();
        })
        .then((data) => {
            const modalBody = document.getElementById("modal-body");
            if (data.success) {
                modalBody.innerHTML = `<div class="text-center text-green-600 font-bold text-lg">${data.message}</div>`;
            } else {
                modalBody.innerHTML = `<div class="text-center text-red-500 font-bold text-lg">${data.error}</div>`;
            }
        })
        .catch((error) => {
            console.error("Error en la subida del certificado:", error);
            const modalBody = document.getElementById("modal-body");
            modalBody.innerHTML = `<div class="text-center text-red-500 font-bold text-lg">Error: ${error.message}</div>`;
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = "Subir Certificado";
        });
    }

    // Se ha movido la llamada a la función para limpiar el mensaje de error al final del evento de 'input'.
    document.getElementById("cedula").addEventListener("input", function () {
        const errorMessageDiv = document.getElementById("cedula-error-message");
        const cedulaInput = document.getElementById("cedula");
        if (!errorMessageDiv.classList.contains("hidden")) {
            errorMessageDiv.classList.add("hidden");
            cedulaInput.classList.remove("border-red-500", "focus:ring-red-500");
        }
    });
});
