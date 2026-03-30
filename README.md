<<<<<<< HEAD
# SafeTrack — Sistema de Registro de Incidentes de Seguridad Laboral

## Stack tecnológico
- Frontend: HTML + CSS + JS Vanilla
- Backend: PHP
- Base de datos 1: MySQL (XAMPP local / Railway en la nube)
- Base de datos 2: PostgreSQL (Supabase en la nube)
=======
# tecnologia-db
# SafeTrackCODE — Sistema de Registro de Incidentes de Seguridad Laboral

SafeTrack es una aplicación web para el registro, seguimiento y gestión de incidentes de seguridad laboral en entornos industriales. Permite a operarios reportar incidentes y a supervisores gestionar su resolución mediante acciones correctivas.

---
Tipo de aplicación: Web
SafeTrack se desarrolla como una aplicación web debido a que el entorno de uso es industrial y multiusuario, donde distintos empleados (operarios y supervisores) necesitan acceder al sistema desde diferentes equipos dentro de la planta sin necesidad de instalar software adicional. Una aplicación web permite el acceso inmediato desde cualquier navegador, facilita el mantenimiento centralizado del sistema y simplifica el despliegue tanto en entornos locales (XAMPP) como en la nube (Render + Supabase), lo que la convierte en la opción más práctica y escalable para las necesidades del proyecto.

## Tecnologías utilizadas

### Frontend
- **HTML5** — estructura de las vistas
- **CSS3** — estilos personalizados (`style.css`) con variables CSS y diseño responsivo
- **JavaScript (Vanilla ES6+)** — lógica del cliente, consumo de API REST, manejo de sesión
- **Google Fonts** — tipografías _Bebas Neue_ y _DM Sans_

### Backend
- **PHP 8.0** — lógica del servidor, controladores y modelos
- **Arquitectura MVC** — separación en controladores (`auth_controller.php`, `incidente_controller.php`) y modelos (`mysql_model.php`, `supabase_model.php`)
- **Sesiones PHP** — autenticación con `session_start()` / `session_destroy()`
- **API REST** — punto de entrada único en `backend/api/index.php`, enrutamiento por parámetro `action`

### Base de datos
- **MySQL / MariaDB** (local con XAMPP) — motor principal
- **PostgreSQL vía Supabase** (opcional, para despliegue en la nube)
- **MD5** — hash de contraseñas

### Servidor local
- **XAMPP (Apache 2.4 + PHP 8.0 + MariaDB)** — entorno de desarrollo
>>>>>>> 9d9f2b67295bbca2472367e5da850375561cef14

---

## Estructura del proyecto

```
safetrack/
├── frontend/
<<<<<<< HEAD
│   ├── login.html
│   ├── dashboard.html
│   ├── incidentes.html
│   ├── nuevo_incidente.html
│   ├── acciones.html
│   ├── css/style.css
│   └── js/app.js
├── backend/
│   ├── config/database.php     ← CREDENCIALES AQUÍ
=======
│   ├── login.html            # Pantalla de acceso
│   ├── dashboard.html        # Panel principal con estadísticas
│   ├── incidentes.html       # Listado de incidentes
│   ├── nuevo_incidente.html  # Formulario de registro
│   ├── acciones.html         # Acciones correctivas
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js            # Funciones globales compartidas
│
├── backend/
│   ├── config/
│   │   └── database.php      # Configuración MySQL y Supabase
>>>>>>> 9d9f2b67295bbca2472367e5da850375561cef14
│   ├── models/
│   │   ├── mysql_model.php
│   │   └── supabase_model.php
│   ├── controllers/
│   │   ├── auth_controller.php
│   │   └── incidente_controller.php
<<<<<<< HEAD
│   └── api/index.php
├── mysql_incidentes.sql
├── postgres_incidentes.sql
=======
│   └── api/
│       └── index.php         # Punto de entrada de la API REST
│
├── mysql_incidentes.sql      # Script de base de datos MySQL
├── postgres_incidentes.sql   # Script de base de datos PostgreSQL
>>>>>>> 9d9f2b67295bbca2472367e5da850375561cef14
└── README.md
```

---

<<<<<<< HEAD
## Instalación local (XAMPP)

1. Copiar la carpeta `safetrack/` dentro de `htdocs/` de XAMPP
2. Ejecutar `mysql_incidentes.sql` en phpMyAdmin
3. Ejecutar `postgres_incidentes.sql` en Supabase SQL Editor
4. Editar `backend/config/database.php` con tus credenciales
5. Acceder desde: `http://localhost/safetrack/frontend/login.html`

## Credenciales de prueba
- Supervisor: `ana.gomez` / `1234`
- Operario:   `carlos.perez` / `1234`

---

## Cosas que DEBES cambiar antes de usar

### backend/config/database.php
- `SUPABASE_URL` → tu URL de Supabase
- `SUPABASE_KEY` → tu API Key de Supabase

### frontend/js/app.js
- `const API` → ruta correcta según entorno

### backend/api/index.php
- `Access-Control-Allow-Origin: *` → cambiar por tu dominio en producción
- `motor` → cambiar a `supabase` si está desplegado en Render

---

## Despliegue en Render (opcional)

1. Subir proyecto a GitHub
2. Crear servicio Web en Render → conectar repositorio
3. Configurar variables de entorno en Render con las credenciales
4. Cambiar `const API` en `app.js` por la URL de Render
5. Cambiar motor de login a `supabase` en `api/index.php`

---

## Diferencias técnicas MySQL vs PostgreSQL

| Característica | MySQL | PostgreSQL |
|---|---|---|
| Auto incremento | AUTO_INCREMENT | SERIAL |
| Fecha/hora | DATETIME | TIMESTAMP |
| Roles | GRANT básico | Row Level Security (RLS) |
| Hash password | MD5() | md5() |
| Foreign key | Al final de la tabla | REFERENCES inline |
=======
## Base de datos

El sistema cuenta con **11 tablas** y **2 vistas**:

### Tablas
| Tabla | Descripción |
|---|---|
| `areas` | Áreas físicas de la empresa |
| `tipos_incidente` | Clasificación de incidentes |
| `niveles_gravedad` | Niveles: Leve, Moderada, Alta, Crítica |
| `estados_incidente` | Estados: Abierto, En proceso, Cerrado |
| `cargos` | Cargos del personal |
| `roles` | Roles del sistema (operario, supervisor) |
| `empleados` | Datos del personal |
| `usuarios` | Cuentas de acceso al sistema |
| `incidentes` | Registro principal de incidentes |
| `acciones_correctivas` | Acciones tomadas por incidente |
| `empleados_incidentes` | Empleados involucrados en cada incidente |

### Vistas
| Vista | Descripción |
|---|---|
| `v_incidentes_sin_resolver` | Incidentes abiertos o en proceso por área |
| `v_frecuencia_por_tipo` | Frecuencia histórica de incidentes por tipo |

---

## Instalación local (XAMPP)

1. Clonar o copiar la carpeta `safetrack/` en `c:\xampp\htdocs\code\`
2. Iniciar **Apache** y **MySQL** desde el XAMPP Control Panel
3. Abrir **phpMyAdmin** en `localhost/phpmyadmin`
4. Importar `mysql_incidentes.sql`
5. Acceder a la aplicación en:
   ```
   http://localhost/code/frontend/login.html
   ```

---

## Credenciales de prueba

| Usuario | Contraseña | Rol |
|---|---|---|
| `carlos.perez` | `1234` | Operario |
| `ana.gomez` | `1234` | Supervisor |
| `luis.mamani` | `1234` | Operario |
| `sandra.flores` | `1234` | Supervisor |

> Los supervisores pueden cerrar incidentes. Los operarios solo pueden registrar y consultar.

---

## Roles y permisos

| Acción | Operario | Supervisor |
|---|---|---|
| Ver incidentes | ✅ | ✅ |
| Registrar incidente | ✅ | ✅ |
| Registrar acción correctiva | ✅ | ✅ |
| Cerrar incidente | ❌ | ✅ |

---

## Despliegue en la nube (opcional)

El sistema soporta despliegue con:
- **Render** — para el backend PHP
- **Supabase** — para la base de datos PostgreSQL

Para activar Supabase, configurar las constantes `SUPABASE_URL` y `SUPABASE_KEY` en `backend/config/database.php` y cambiar el parámetro `motor` a `supabase` en `backend/api/index.php`.
>>>>>>> 9d9f2b67295bbca2472367e5da850375561cef14
