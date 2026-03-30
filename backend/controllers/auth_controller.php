<?php
// ============================================================
// CONTROLADOR DE AUTENTICACIÓN
// Usa token simple en vez de sesiones PHP
// Compatible con XAMPP local y Render en la nube
// ============================================================
require_once __DIR__ . '/../models/mysql_model.php';
require_once __DIR__ . '/../models/supabase_model.php';

class AuthController {

    public function login($username, $password, $motor = 'mysql') {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Usuario y contraseña requeridos'];
        }

        if ($motor === 'supabase') {
            $model   = new SupabaseModel();
            $usuario = $model->loginUsuario($username, $password);
            if (!$usuario) {
                return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
            }
            $nombre = isset($usuario['empleados']) ? $usuario['empleados']['nombre'] : $username;
            $rol    = isset($usuario['roles'])     ? $usuario['roles']['nombre_rol'] : '';
            $id     = $usuario['id_usuario'] ?? null;
        } else {
            $model   = new MySQLModel();
            $usuario = $model->loginUsuario($username, $password);
            if (!$usuario) {
                return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
            }
            $nombre = $usuario['nombre']     ?? $username;
            $rol    = $usuario['nombre_rol'] ?? '';
            $id     = $usuario['id_usuario'] ?? null;
        }

        // Generar token simple con los datos del usuario
        $token_data = [
            'id'       => $id,
            'username' => $username,
            'nombre'   => $nombre,
            'rol'      => $rol,
            'exp'      => time() + (60 * 60 * 8) // 8 horas
        ];

        // Codificar en base64 como token simple
        $token = base64_encode(json_encode($token_data));

        return [
            'success' => true,
            'message' => 'Acceso correcto',
            'token'   => $token,
            'usuario' => [
                'id'       => $id,
                'username' => $username,
                'nombre'   => $nombre,
                'rol'      => $rol
            ]
        ];
    }

    public function logout() {
        // El frontend elimina el token de localStorage
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }

    public function verificarSesion($token = null) {
        if (empty($token)) {
            return ['autenticado' => false];
        }

        // Decodificar token
        $data = json_decode(base64_decode($token), true);
        if (!$data) {
            return ['autenticado' => false];
        }

        // Verificar expiración
        if ($data['exp'] < time()) {
            return ['autenticado' => false, 'message' => 'Sesión expirada'];
        }

        return [
            'autenticado' => true,
            'usuario'     => [
                'id'       => $data['id'],
                'username' => $data['username'],
                'nombre'   => $data['nombre'],
                'rol'      => $data['rol']
            ]
        ];
    }
}