<?php
// ============================================================
// MODELO SUPABASE (PostgreSQL en la nube)
// Se conecta via API REST usando curl
// ============================================================
require_once __DIR__ . '/../config/database.php';

class SupabaseModel {

    // ── Incidentes ─────────────────────────────────────────
    public function getIncidentes() {
        return supabaseRequest('incidentes', 'GET', null,
            '?select=id_incidente,fecha_hora,descripcion,id_estado,' .
            'tipos_incidente(nombre_tipo),' .
            'areas(nombre_area),' .
            'niveles_gravedad(nombre_gravedad),' .
            'estados_incidente(nombre_estado)' .
            '&order=fecha_hora.desc'
        );
    }

    public function getIncidentesSinResolver() {
        return supabaseRequest('v_incidentes_sin_resolver');
    }

    public function getFrecuenciaPorTipo() {
        return supabaseRequest('v_frecuencia_por_tipo');
    }

    public function insertarIncidente($datos) {
        return supabaseRequest('incidentes', 'POST', [
            'descripcion'    => $datos['descripcion'],
            'id_tipo'        => (int)$datos['id_tipo'],
            'id_area'        => (int)$datos['id_area'],
            'id_gravedad'    => (int)$datos['id_gravedad'],
            'id_estado'      => 1,
            'id_usuario_reg' => (int)$datos['id_usuario_reg']
        ]);
    }

    public function cerrarIncidente($id_incidente) {
        return supabaseRequest('incidentes', 'PATCH',
            ['id_estado' => 3],
            '?id_incidente=eq.' . (int)$id_incidente
        );
    }

    // ── Acciones correctivas ───────────────────────────────
    public function getAcciones() {
        return supabaseRequest('acciones_correctivas', 'GET', null,
            '?select=*,incidentes(descripcion),usuarios(username)&order=fecha_accion.desc'
        );
    }

    public function insertarAccion($datos) {
        return supabaseRequest('acciones_correctivas', 'POST', [
            'id_incidente' => (int)$datos['id_incidente'],
            'descripcion'  => $datos['descripcion'],
            'id_usuario'   => (int)$datos['id_usuario']
        ]);
    }

    // ── Catálogos ──────────────────────────────────────────
    public function getAreas() {
        return supabaseRequest('areas', 'GET', null, '?order=nombre_area.asc');
    }

    public function getTipos() {
        return supabaseRequest('tipos_incidente', 'GET', null, '?order=nombre_tipo.asc');
    }

    public function getGravedades() {
        return supabaseRequest('niveles_gravedad', 'GET', null, '?order=id_gravedad.asc');
    }

    // ── Estadísticas dashboard ─────────────────────────────
    // Consulta directamente por id_estado sin join embebido
    // id_estado: 1=Abierto, 2=En proceso, 3=Cerrado
    public function getStats() {
        $todos = supabaseRequest('incidentes', 'GET', null, '?select=id_estado');
        $stats = ['total' => 0, 'abiertos' => 0, 'en_proceso' => 0, 'cerrados' => 0];
        if (!$todos) return $stats;
        foreach ($todos as $inc) {
            $stats['total']++;
            $id_estado = (int)($inc['id_estado'] ?? 0);
            if ($id_estado === 1) $stats['abiertos']++;
            if ($id_estado === 2) $stats['en_proceso']++;
            if ($id_estado === 3) $stats['cerrados']++;
        }
        return $stats;
    }

    // ── Login ──────────────────────────────────────────────
    public function loginUsuario($username, $password) {
        $hash      = md5($password);
        $resultado = supabaseRequest('usuarios', 'GET', null,
            '?select=id_usuario,username,id_rol,empleados(nombre,apellido),roles(nombre_rol)' .
            '&username=eq.'      . urlencode($username) .
            '&password_hash=eq.' . urlencode($hash) .
            '&limit=1'
        );
        return (!empty($resultado)) ? $resultado[0] : null;
    }
}