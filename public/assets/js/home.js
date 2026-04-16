// Se asegura de que el código se ejecute una vez que el DOM esté completamente cargado.
document.addEventListener('DOMContentLoaded', () => {
    const basePath = document.body.dataset.basePath || "";
    const buildUrl = (path) => `${basePath}${path}`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
    const cedulaInput = document.getElementById("cedula");
    const passwordInput = document.getElementById("password");
    const cedulaErrorMessageDiv = document.getElementById("cedula-error-message");
    const passwordErrorMessageDiv = document.getElementById("password-error-message");

    document.getElementById("toggle-password")?.addEventListener("click", function () {
        const icon = this.querySelector("i");
        const nextType = passwordInput.type === "password" ? "text" : "password";
        passwordInput.type = nextType;
        icon.classList.toggle("bi-eye", nextType === "password");
        icon.classList.toggle("bi-eye-slash", nextType !== "password");
    });

    // Listener para el formulario de login
    document.getElementById("buscar-form").addEventListener("submit", async function (e) {
        e.preventDefault(); // Previene el envío tradicional del formulario

        const cedula = cedulaInput.value.trim(); // Usa trim() para limpiar espacios
        const password = passwordInput.value;
        let hasError = false;

        // Validación de la cédula (debe tener 10 dígitos)
        if (!/^\d{10}$/.test(cedula)) {
            cedulaErrorMessageDiv.textContent = "Por favor, ingresa una cédula válida de 10 dígitos.";
            cedulaErrorMessageDiv.classList.remove("hidden"); // Muestra el mensaje de error
            cedulaInput.classList.add("border-red-500", "focus:ring-red-500"); // Resalta el input en rojo
            hasError = true;
        } else {
            cedulaErrorMessageDiv.classList.add("hidden"); // Oculta el mensaje si era válido
            cedulaInput.classList.remove("border-red-500", "focus:ring-red-500"); // Quita el resaltado rojo
        }

        if (!password.trim()) {
            passwordErrorMessageDiv.textContent = "Ingresa tu contraseña para continuar.";
            passwordErrorMessageDiv.classList.remove("hidden");
            passwordInput.classList.add("border-red-500", "focus:ring-red-500");
            hasError = true;
        } else {
            passwordErrorMessageDiv.classList.add("hidden");
            passwordInput.classList.remove("border-red-500", "focus:ring-red-500");
        }

        if (hasError) {
            return;
        }

        // Muestra un indicador de carga
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.textContent = "Validando...";
        submitButton.disabled = true;

        // Se construye la URL para el fetch.
        const fetchUrl = buildUrl("/becario/login");

        try {
            const response = await fetch(fetchUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "X-CSRF-Token": csrfToken,
                },
                body: "cedula=" + encodeURIComponent(cedula) + "&password=" + encodeURIComponent(password),
            });

            // Siempre parseamos JSON: el servidor siempre devuelve JSON desde /becario/login
            const result = await response.json();

            const modal = document.getElementById("modal");
            const modalContent = modal.querySelector(".modal-content");
            const modalBody = document.getElementById("modal-body");

            if (result.success) {
                if (result.redirect) {
                    window.location.href = result.redirect;
                    return;
                }
                // require_change o becario_form → mostrar HTML en modal
                modalBody.innerHTML = result.html || `<p class="text-red-500 font-bold text-lg">Error al cargar el formulario.</p>`;
                modal.style.display = "flex";
                setTimeout(() => {
                    modalContent.classList.remove("scale-95", "opacity-0");
                    modalContent.classList.add("scale-100", "opacity-100");
                    attachFormListeners();
                }, 10);
            } else if (result.html) {
                // Usuario no encontrado (becario_not_found) → mostrar en modal
                modalBody.innerHTML = result.html;
                modal.style.display = "flex";
                setTimeout(() => {
                    modalContent.classList.remove("scale-95", "opacity-0");
                    modalContent.classList.add("scale-100", "opacity-100");
                }, 10);
            } else {
                // Error de credenciales → mostrar en campo contraseña
                passwordErrorMessageDiv.textContent = result.error || "Cédula o contraseña incorrecta.";
                passwordErrorMessageDiv.classList.remove("hidden");
                passwordInput.classList.add("border-red-500", "focus:ring-red-500");
            }

        } catch (error) {
            console.error("Error en la solicitud Fetch:", error);
            passwordErrorMessageDiv.textContent = "Error de conexión. Intenta nuevamente.";
            passwordErrorMessageDiv.classList.remove("hidden");
            passwordInput.classList.add("border-red-500", "focus:ring-red-500");

        } finally {
            submitButton.textContent = "Ingresar";
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
    cedulaInput.addEventListener("input", function () {
        if (!cedulaErrorMessageDiv.classList.contains("hidden")) {
            cedulaErrorMessageDiv.classList.add("hidden");
            cedulaInput.classList.remove("border-red-500", "focus:ring-red-500");
        }
    });

    passwordInput.addEventListener("input", function () {
        if (!passwordErrorMessageDiv.classList.contains("hidden")) {
            passwordErrorMessageDiv.classList.add("hidden");
            passwordInput.classList.remove("border-red-500", "focus:ring-red-500");
        }
    });

    // Adjunta listeners a los formularios dinámicos cargados en el modal.
    function attachFormListeners() {
        const modalBody = document.getElementById("modal-body");
        if (!modalBody) return;

        // Formulario de cambio de contraseña (primer ingreso)
        const changePasswordForm = modalBody.querySelector("#change-password-form");
        if (changePasswordForm && !changePasswordForm.dataset.listenerAttached) {
            changePasswordForm.dataset.listenerAttached = "true";
            attachChangePasswordListeners(changePasswordForm);
        }

    }

    // Maneja el formulario de cambio de contraseña dentro del modal.
    function attachChangePasswordListeners(form) {
        const modalBody = document.getElementById("modal-body");

        // Toggle visibilidad de campos de contraseña
        form.querySelectorAll(".cp-toggle").forEach(function (btn) {
            btn.addEventListener("click", function () {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                if (!input) return;
                const icon = this.querySelector("i");
                input.type = input.type === "password" ? "text" : "password";
                icon.classList.toggle("bi-eye", input.type === "password");
                icon.classList.toggle("bi-eye-slash", input.type !== "password");
            });
        });

        // Indicador de fortaleza en tiempo real
        const newPwdInput = document.getElementById("cp-new-password");
        if (newPwdInput) {
            newPwdInput.addEventListener("input", function () {
                updateCpStrength(this.value);
            });
        }

        // Envío del formulario vía AJAX
        form.addEventListener("submit", async function (e) {
            e.preventDefault();

            const currentPwd = document.getElementById("cp-current-password")?.value || "";
            const newPwd   = document.getElementById("cp-new-password")?.value || "";
            const confirmPwd = document.getElementById("cp-confirm-password")?.value || "";
            const msgDiv   = document.getElementById("cp-message");
            const submitBtn = document.getElementById("cp-submit");

            if (newPwd !== confirmPwd) {
                showCpMessage(msgDiv, "error", "Las contraseñas nuevas no coinciden.");
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';

            try {
                const formData = new FormData(form);
                const response = await fetch(buildUrl("/becario/change-password"), {
                    method: "POST",
                    body: formData,
                    headers: { "X-CSRF-Token": csrfToken },
                });

                const result = await response.json();

                if (result.success) {
                    if (result.redirect) {
                        window.location.href = result.redirect;
                        return;
                    }
                    if (result.html) {
                        modalBody.innerHTML = result.html;
                        attachFormListeners();
                    }
                } else {
                    showCpMessage(msgDiv, "error", result.error || "Error al cambiar la contraseña.");
                }
            } catch (err) {
                console.error("Error en cambio de contraseña:", err);
                showCpMessage(msgDiv, "error", "Error de conexión. Intenta nuevamente.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Cambiar contraseña y continuar';
            }
        });
    }

    function showCpMessage(div, type, msg) {
        if (!div) return;
        div.classList.remove("hidden", "bg-green-100", "text-green-800", "bg-red-100", "text-red-800");
        if (type === "error") {
            div.classList.add("bg-red-100", "text-red-800");
        } else {
            div.classList.add("bg-green-100", "text-green-800");
        }
        div.innerHTML = (type === "error" ? '<i class="bi bi-exclamation-circle mr-2"></i>' : '<i class="bi bi-check-circle mr-2"></i>') + msg;
    }

    function updateCpStrength(password) {
        const container = document.getElementById("cp-strength-container");
        if (!container) return;

        if (!password) { container.classList.add("hidden"); return; }
        container.classList.remove("hidden");

        const checks = {
            length:    password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            special:   /[!@#$%^&*()_+\-=\[\]{};:'",.<>?/\\|`~]/.test(password),
        };
        const reqIds = { length: "cp-req-length", uppercase: "cp-req-uppercase", lowercase: "cp-req-lowercase", special: "cp-req-special" };

        Object.keys(checks).forEach(function (key) {
            const el = document.getElementById(reqIds[key]);
            if (!el) return;
            const ico = el.querySelector("i");
            if (checks[key]) {
                el.classList.replace("text-gray-400", "text-green-600");
                ico && ico.classList.replace("bi-circle-fill", "bi-check-circle-fill");
            } else {
                el.classList.replace("text-green-600", "text-gray-400");
                ico && ico.classList.replace("bi-check-circle-fill", "bi-circle-fill");
            }
        });

        const strength = Object.values(checks).filter(Boolean).length;
        const colors = ["", "bg-red-500", "bg-yellow-500", "bg-blue-500", "bg-green-500"];
        const labels = ["", "Muy débil", "Débil", "Buena", "Excelente"];

        const fill = document.getElementById("cp-strength-fill");
        const text = document.getElementById("cp-strength-text");
        if (fill) { fill.style.width = (strength * 25) + "%"; fill.className = `h-full transition-all duration-300 rounded-full ${colors[strength]}`; }
        if (text) { text.textContent = labels[strength]; text.className = `text-xs font-semibold ${colors[strength].replace("bg-", "text-")}`; }
    }

});
