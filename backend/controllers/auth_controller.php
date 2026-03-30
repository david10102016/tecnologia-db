<?php
// ============================================================
// CONTROLADOR DE AUTENTICACIÓN
// Maneja login y logout
// ============================================================
require_once __DIR__ . '/../models/mysql_model.php';
require_once __DIR__ . '/../models/supabase_model.php';

class AuthController {

    public function login($username, $password, $motor = 'mysql') {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Usuario y contraseña requeridos'];
        }

        // Intentar login según el motor activo
        // NOTA: por defecto usa MySQL local
        // Si el proyecto está desplegado en Render, cambia a 'supabase'
        if ($motor === 'supabase') {
            $model   = new SupabaseModel();
            $usuario = $model->loginUsuario($username, $password);
            $nombre  = isset($usuario['empleados']) ? $usuario['empleados']['nombre'] : $username;
            $rol     = isset($usuario['roles'])     ? $usuario['roles']['nombre_rol'] : '';
            $id      = $usuario['id_usuario'] ?? null;
        } else {
            $model   = new MySQLModel();
            $usuario = $model->loginUsuario($username, $password);
            $nombre  = $usuario['nombre']     ?? $username;
            $rol     = $usuario['nombre_rol'] ?? '';
            $id      = $usuario['id_usuario'] ?? null;
        }

        if (!$usuario) {
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
        }

        // Guardar sesión
        session_start();
        $_SESSION['id_usuario'] = $id;
        $_SESSION['username']   = $username;
        $_SESSION['nombre']     = $nombre;
        $_SESSION['rol']        = $rol;

        return [
            'success'  => true,
            'message'  => 'Acceso correcto',
            'usuario'  => [
                'id'       => $id,
                'username' => $username,
                'nombre'   => $nombre,
                'rol'      => $rol
            ]
        ];
    }

    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }

    public function verificarSesion() {
        session_start();
        if (empty($_SESSION['id_usuario'])) {
            return ['autenticado' => false];
        }
        return [
            'autenticado' => true,
            'usuario'     => [
                'id'       => $_SESSION['id_usuario'],
                'username' => $_SESSION['username'],
                'nombre'   => $_SESSION['nombre'],
                'rol'      => $_SESSION['rol']
            ]
        ];
    }
}
