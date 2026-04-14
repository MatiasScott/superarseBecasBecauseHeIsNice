// Se asegura de que el código se ejecute una vez que el DOM esté completamente cargado.
document.addEventListener('DOMContentLoaded', () => {
    const basePath = document.body.dataset.basePath || "";
    const buildUrl = (path) => `${basePath}${path}`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

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
        const fetchUrl = buildUrl("/becario/buscar");

        try {
            // Realiza la petición Fetch al controlador PHP
            const response = await fetch(fetchUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "X-CSRF-Token": csrfToken,
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
                modalBody.innerHTML = result.html || `<p class="text-red-500 font-bold text-lg">Error al cargar la vista del formulario.</p>`;
                modal.style.display = "flex";
                setTimeout(() => {
                    modalContent.classList.remove("scale-95", "opacity-0");
                    modalContent.classList.add("scale-100", "opacity-100");
                    attachFormListeners();
                }, 10);
            } else {
                modalBody.innerHTML = result.html || `<p class="text-red-500 font-bold text-lg">${result.error}</p>`;
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
            submitButton.textContent = "Buscar";
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

    // Función para adjuntar los listeners a los formularios que se cargan en el modal.
    function attachFormListeners() {
        const certificadoForm = document
            .getElementById("modal-body")
            .querySelector("form[action$='becario/procesarSubida']");
        if (certificadoForm) {
            certificadoForm.addEventListener("submit", handleCertificadoFormSubmission);
        }
    }

    function handleCertificadoFormSubmission(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = "Subiendo...";

        // Se ha corregido la URL para que sea relativa a la raíz del sitio
        fetch(buildUrl("/becario/procesarSubida"), {
            method: form.method,
            headers: {
                "X-CSRF-Token": csrfToken,
            },
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

});
