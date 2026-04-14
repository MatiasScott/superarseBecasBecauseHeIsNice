# Landing Page Becas Because He Is Nice

Proyecto PHP con arquitectura MVC básica.

## Estructura

- app/models: acceso y lógica de datos.
- app/controllers: orquestación, validación y seguridad de flujos.
- app/views: solo presentación.
- public/index.php: front controller y router.

## Variables de entorno para base de datos

El proyecto usa variables de entorno para conexión:

- DB_HOST (por defecto 127.0.0.1)
- DB_NAME (por defecto becasupe_sistema_usuarios)
- DB_USER (por defecto root)
- DB_PASS (por defecto vacío)

## Seguridad aplicada

- Token CSRF para solicitudes POST.
- Verificación de ownership por cédula en sesión para actualizar/subir/descargar.
- Validación robusta de subida de archivos:
	- Solo PDF válido por extensión y MIME.
	- Tamaño máximo 5 MB.
- Endurecimiento de sesión y cabeceras HTTP de seguridad.
- Eliminación de exposición de contraseña en vistas.

## Notas importantes

- No versionar datos sensibles reales en SQL (credenciales, contraseñas, PII).
- Recomendado migrar la columna contrasenia a hashes y flujo de restablecimiento.
