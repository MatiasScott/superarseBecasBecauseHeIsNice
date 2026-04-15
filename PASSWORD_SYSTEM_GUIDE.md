# Sistema de Gestión de Contraseñas Seguro

## Descripción General

Se ha implementado un sistema completo y separado de gestión de contraseñas para LOGIN del sistema:
- **Nueva columna:** `contrasenia_login` (para autenticación del sistema)
- **Columna existente:** `contrasenia` (para Moodle, NO se modifica)
- Inicialización automática de contraseñas (cédula hasheada con bcrypt)
- Validación de requisitos de contraseña fuerte
- Interfaz para cambiar contraseña
- Protección CSRF en todos los endpoints

## Cambios Realizados

### 0. Estructura de Base de Datos

Se agregó nueva columna en tabla `usuarios`:

```sql
ALTER TABLE `usuarios` 
ADD COLUMN `contrasenia_login` VARCHAR(255) NULL DEFAULT NULL AFTER `contrasenia`;
```

**Separación clara de responsabilidades:**

| Columna | Propósito | Tipo | Hash | Nota |
|---------|-----------|------|------|------|
| `contrasenia` | Moodle/Plataforma | VARCHAR(50) | Plain? | NO SE MODIFICA |
| `contrasenia_login` | Login Sistema | VARCHAR(255) | Bcrypt | **Nueva columna** |

**Archivo de migración:** `BDD/001_add_contrasenia_login_column.sql`

### 1. Modelo Becario (`app/models/Becario.php`)

Se agregaron 3 nuevos métodos para gestionar contraseñas de forma segura:

```php
// Hashea una contraseña usando bcrypt
public function hashPassword($password): string

// Verifica si una contraseña coincide con su hash
public function verifyPassword($password, $hash): bool

// Actualiza la contraseña de un becario
public function updatePassword($cedula, $passwordHash): bool
```

### 2. Controlador Becario (`app/controllers/BecarioController.php`)

#### Métodos Agregados:

**`validatePasswordRequirements($password)`** (privada)
- Valida que la contraseña cumpla requisitos:
  - Mínimo 8 caracteres
  - Al menos 1 letra mayúscula
  - Al menos 1 letra minúscula
  - Al menos 1 carácter especial
- Retorna array con errores detallados si no cumple

**`changePassword()`** (pública)
- Maneja GET y POST en `/becario/change-password`
- GET: Renderiza formulario de cambio de contraseña (requiere sesión autorizada)
- POST: Procesa cambio de contraseña con validaciones de seguridad:
  - CSRF token validation
  - Authorization check
  - Password requirements validation
  - Bcrypt hashing

#### Métodos Modificados:

**`buscar()`**
- Ahora inicializa la contraseña de login automáticamente si es NULL
- Verifica columna: `contrasenia_login`
- Primera búsqueda de un usuario establece su contraseña inicial = cédula (hasheada)
- No requiere acción manual del administrador
- **NO modifica:** `contrasenia` (Moodle)

**`procesar()`**
- Agrega URL de cambio de contraseña hacia la vista registro_exitoso.php

### 3. Router (`public/index.php`)

Se agregó nueva ruta:
```php
case '/becario/change-password':
    $becarioController->changePassword();
    break;
```

### 4. Vistas

#### Nueva Vista: `app/views/change_password.php`
Formulario interactivo para cambiar contraseña con:
- Toggle de visibilidad de contraseña
- Validación en tiempo real de requisitos
- Indicador visual de fortaleza de contraseña
- Confirmación de contraseña
- Mensajes de error/éxito
- Protección CSRF

#### Vista Modificada: `app/views/registro_exitoso.php`
- Agrega botón "Cambiar Contraseña" (color naranja)
- Enlaza a `/becario/change-password`

### 5. Migración de Contraseñas

#### Archivos de Migración:

**`BDD/001_add_contrasenia_login_column.sql`**
- Script de creación de columna `contrasenia_login`
- Ejecutar una sola vez en la BD

**`BDD/002_init_contrasenia_login.sql`**
- Documentación de estrategias de inicialización
- Incluye script PHP para migración en batch

**`scripts/migrate-passwords.php`**
- Script ejecutable para inicializar todas las contraseñas
- Uso: `php scripts/migrate-passwords.php`

#### Estrategia de Migración:

**Automática (RECOMENDADA - Sin downtime):**
- Primera búsqueda de un usuario → Si no tiene `contrasenia_login` → Se inicializa con cédula (hasheada)
- No requiere scripts adicionales
- Gradual: cada usuario se inicializa en su primer acceso

**Manual (Batch - Para inicializar todas a la vez):**
```bash
cd c:\xampp\htdocs\landingPage_BecasBecauseheisnice
php scripts/migrate-passwords.php
```
Resultado: Todos los usuarios sin `contrasenia_login` reciben hash bcrypt(cedula)

**Verificación posterior:**
```sql
SELECT cedula, contrasenia, contrasenia_login FROM usuarios LIMIT 5;
```

## Requisitos de Contraseña

Todas las nuevas contraseñas deben cumplir:
```
✓ Mínimo 8 caracteres
✓ Al menos 1 mayúscula
✓ Al menos 1 minúscula
✓ Al menos 1 carácter especial: !@#$%^&*()_+-=[]{}';:",.<>?/\|`~
```

## Flujo de Uso

### Para el Usuario (Cambiar Contraseña)

1. Usuario accede a su perfil (página registro_exitoso.php)
2. Hace clic en botón "Cambiar Contraseña"
3. Se abre formulario en `/becario/change-password`
4. Ingresa nueva contraseña (se valida en tiempo real)
5. Confirma contraseña
6. Sistema verifica:
   - ✓ CSRF token válido
   - ✓ Usuario autorizado (sesión)
   - ✓ Contraseña cumple requisitos
7. Se guarda hash bcrypt en base de datos
8. Redirección a profil con mensaje de éxito

### Para Nuevos Usuarios

1. Usuario ingresa su cédula
2. Sistema busca usuario en BD
3. Si contraseña es NULL → Se inicializa con cédula (hasheada)
4. Usuario puede cambiar contraseña cuando guste

## Seguridad

### Hashing
- Algoritmo: bcrypt (PASSWORD_DEFAULT en PHP)
- Costo: 10 (por defecto)
- Seguridad: Resistente a ataques de fuerza bruta

### Protecciones Implementadas
```
- CSRF token en formulario y headers
- Authorization check (sesión)
- Input validation (cédula 10 dígitos)
- Password requirements enforcement
- Respuestas genéricas en errores (no revelan si usuario existe)
- HttpOnly cookies
- SameSite=Lax
```

### Recomendaciones Futuras
- [ ] Implementar historial de cambios de contraseña
- [ ] Implementar "forgot password" con email
- [ ] Agregar 2FA (Two Factor Authentication)
- [ ] Comprometer senha con bases de datos de contraseñas comunes
- [ ] Agregar logs de acceso fallido

## Testing

### Para Verificar que Funciona:

#### 1. Verificar Estructura de BD:
```sql
DESC usuarios;
-- Debe mostrar ambas columnas:
-- contrasenia     | VARCHAR(50)  | Moodle
-- contrasenia_login | VARCHAR(255) | Sistema (NUEVA)
```

#### 2. Búsqueda inicial (inicializa contraseña):
```
GET / → Ingresa cédula de usuario sin contrasenia_login
Resultado: 
  ✓ contrasenia_login ≠ NULL (inicializada con bcrypt(cedula))
  ✓ contrasenia sin cambios (sigue siendo Moodle)
```

#### 3. Cambiar contraseña:
```
GET /becario/change-password → Muestra formulario ✓
POST /becario/change-password (contraseña válida) → Éxito ✓
DB: contrasenia_login = nuevo hash ✓
```

#### 4. Validaciones:
```
POST /becario/change-password (7 chars) → Error ✓
POST /becario/change-password (sin mayúscula) → Error ✓
POST /becario/change-password (sin minúscula) → Error ✓
POST /becario/change-password (sin especial) → Error ✓
```

#### 5. Integridad de datos:
```sql
-- Verificar separación de columnas
SELECT cedula, 
       contrasenia as moodle_pwd, 
       contrasenia_login as login_pwd
FROM usuarios 
WHERE contrasenia_login IS NOT NULL;
-- contrasenia debe ser NULL o valor Moodle (sin cambios)
-- contrasenia_login debe ser hash bcrypt
```

## Variables de Entorno

No se requieren cambios en `.env`. Sistema usa:
- Base de datos: DB_NAME, DB_USER, DB_PASS (existentes)
- Password hashing: bcrypt nativo de PHP (no necesita config)

## Archivos Modificados/Creados

**Modificados:**
```
✓ app/models/Becario.php
  • updatePassword() ahora usa contrasenia_login

✓ app/controllers/BecarioController.php
  • buscar() verifica contrasenia_login, inicializa si es NULL

✓ public/index.php (sin cambios, ruta ya agregada)

✓ app/views/registro_exitoso.php (botón ya agregado)
```

**Nuevos Archivos:**
```
✓ BDD/001_add_contrasenia_login_column.sql
  • Migración SQL para crear columna

✓ BDD/002_init_contrasenia_login.sql
  • Guía de inicialización con opciones

✓ scripts/migrate-passwords.php
  • Script PHP para migración en batch (EJECUTABLE)

✓ app/views/change_password.php (ya existía)

✓ PASSWORD_SYSTEM_GUIDE.md (documentación - actualizada)

✓ BDD/PASSWORD_MIGRATION.sql (documentación anterior - puede ignorarse)
```

## Próximos Pasos Recomendados

### 1. INMEDIATO - Ejecutar Migración SQL
```bash
# Importar en PHPMyAdmin o CLI:
mysql -u root becasupe_sistema_usuarios < BDD/001_add_contrasenia_login_column.sql
```
Verifica: `DROP COLUMN` si ya existe (sin error)

### 2. OPCIONAL - Inicializar Todas las Contraseñas (Batch)
```bash
cd c:\xampp\htdocs\landingPage_BecasBecauseheisnice
php scripts/migrate-passwords.php
```
Resultado: Todos los usuarios sin `contrasenia_login` reciben hash bcrypt(cedula)

### 3. VERIFICACIÓN - Confirmar Estructura
```sql
SELECT cedula, contrasenia, contrasenia_login 
FROM usuarios 
WHERE contrasenia_login IS NOT NULL 
LIMIT 5;
```
Esperado: `contrasenia_login` = hash bcrypt, `contrasenia` = sin cambios

### 4. TESTING - Probar Flujo Completo
- [ ] Ingresa cédula → Verifica contrasenia_login se inicializa
- [ ] Cambiar contraseña → Verifica nuevo hash se guarda
- [ ] Validaciones → Verifica requisitos se cumplen

### 5. MONITOREO - Production Ready
- [ ] Implementar logs de cambios de contraseña
- [ ] Agregar alertas de acceso fallido
- [ ] Revisar permisos de BD (solo actualizar contrasenia_login)
- [ ] Considerar 2FA or TOTP para futuro

---
Última actualización: 14 de abril de 2026
Sistema: Gestor de Becas - Because He Is Nice
Versión Password System: 2.0
