# SafeTrack — Sistema de Registro de Incidentes de Seguridad Laboral

## Stack tecnológico
- Frontend: HTML + CSS + JS Vanilla
- Backend: PHP
- Base de datos 1: MySQL (XAMPP local / Railway en la nube)
- Base de datos 2: PostgreSQL (Supabase en la nube)

---

## Estructura del proyecto

```
safetrack/
├── frontend/
│   ├── login.html
│   ├── dashboard.html
│   ├── incidentes.html
│   ├── nuevo_incidente.html
│   ├── acciones.html
│   ├── css/style.css
│   └── js/app.js
├── backend/
│   ├── config/database.php     ← CREDENCIALES AQUÍ
│   ├── models/
│   │   ├── mysql_model.php
│   │   └── supabase_model.php
│   ├── controllers/
│   │   ├── auth_controller.php
│   │   └── incidente_controller.php
│   └── api/index.php
├── mysql_incidentes.sql
├── postgres_incidentes.sql
└── README.md
```

---

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
