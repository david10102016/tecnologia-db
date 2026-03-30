<?php
// ============================================================
// MODELO MYSQL
// Todas las consultas hacia MySQL (XAMPP / Railway)
// ============================================================
require_once __DIR__ . '/../config/database.php';

class MySQLModel {

    private $conn;

    public function __construct() {
        $this->conn = conectarMySQL();
    }

    // ── Autenticación ──────────────────────────────────────
    public function loginUsuario($username, $password) {
        $hash = md5($password); // NOTA: en producción real usar password_hash/verify
        $sql  = "SELECT u.id_usuario, u.username, e.nombre, e.apellido, r.nombre_rol
                 FROM usuarios u
                 JOIN empleados e ON u.id_empleado = e.id_empleado
                 JOIN roles r     ON u.id_rol      = r.id_rol
                 WHERE u.username = ? AND u.password_hash = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $username, $hash);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // ── Catálogos ──────────────────────────────────────────
    public function getAreas() {
        $r = $this->conn->query("SELECT * FROM areas ORDER BY nombre_area");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function getTipos() {
        $r = $this->conn->query("SELECT * FROM tipos_incidente ORDER BY nombre_tipo");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function getGravedades() {
        $r = $this->conn->query("SELECT * FROM niveles_gravedad ORDER BY id_gravedad");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function getEstados() {
        $r = $this->conn->query("SELECT * FROM estados_incidente ORDER BY id_estado");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    // ── Incidentes ─────────────────────────────────────────
    public function getIncidentes() {
        $sql = "SELECT i.id_incidente, i.fecha_hora, i.descripcion,
                       t.nombre_tipo, a.nombre_area,
                       g.nombre_gravedad, e.nombre_estado,
                       u.username AS registrado_por
                FROM incidentes i
                JOIN tipos_incidente   t ON i.id_tipo     = t.id_tipo
                JOIN areas             a ON i.id_area     = a.id_area
                JOIN niveles_gravedad  g ON i.id_gravedad = g.id_gravedad
                JOIN estados_incidente e ON i.id_estado   = e.id_estado
                JOIN usuarios          u ON i.id_usuario_reg = u.id_usuario
                ORDER BY i.fecha_hora DESC";
        $r = $this->conn->query($sql);
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function getIncidentesSinResolver() {
        $r = $this->conn->query("SELECT * FROM v_incidentes_sin_resolver");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function getFrecuenciaPorTipo() {
        $r = $this->conn->query("SELECT * FROM v_frecuencia_por_tipo");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    public function insertarIncidente($datos) {
        $sql  = "INSERT INTO incidentes (descripcion, id_tipo, id_area, id_gravedad, id_estado, id_usuario_reg)
                 VALUES (?, ?, ?, ?, 1, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('siiii',
            $datos['descripcion'],
            $datos['id_tipo'],
            $datos['id_area'],
            $datos['id_gravedad'],
            $datos['id_usuario_reg']
        );
        $stmt->execute();
        return $this->conn->insert_id;
    }

    public function cerrarIncidente($id_incidente) {
        // Solo supervisores llegan aquí (validado en el controller)
        $sql  = "UPDATE incidentes SET id_estado = (
                    SELECT id_estado FROM estados_incidente WHERE nombre_estado = 'Cerrado'
                 ) WHERE id_incidente = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id_incidente);
        return $stmt->execute();
    }

    // ── Empleados involucrados ─────────────────────────────
    public function insertarEmpleadoIncidente($id_incidente, $id_empleado, $rol) {
        $sql  = "INSERT IGNORE INTO empleados_incidentes (id_incidente, id_empleado, rol_en_incidente)
                 VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iis', $id_incidente, $id_empleado, $rol);
        return $stmt->execute();
    }

    public function getEmpleados() {
        $r = $this->conn->query("SELECT id_empleado, CONCAT(nombre,' ',apellido) AS nombre_completo FROM empleados ORDER BY nombre");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    // ── Acciones correctivas ───────────────────────────────
    public function getAcciones($id_incidente = null) {
        if ($id_incidente) {
            $sql  = "SELECT ac.*, u.username, i.descripcion AS incidente_desc
                     FROM acciones_correctivas ac
                     JOIN usuarios u ON ac.id_usuario   = u.id_usuario
                     JOIN incidentes i ON ac.id_incidente = i.id_incidente
                     WHERE ac.id_incidente = ?
                     ORDER BY ac.fecha_accion DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $id_incidente);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $sql = "SELECT ac.*, u.username, i.descripcion AS incidente_desc
                FROM acciones_correctivas ac
                JOIN usuarios   u ON ac.id_usuario   = u.id_usuario
                JOIN incidentes i ON ac.id_incidente = i.id_incidente
                ORDER BY ac.fecha_accion DESC";
        return $this->conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function insertarAccion($datos) {
        $sql  = "INSERT INTO acciones_correctivas (id_incidente, descripcion, id_usuario)
                 VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isi',
            $datos['id_incidente'],
            $datos['descripcion'],
            $datos['id_usuario']
        );
        return $stmt->execute();
    }

    // ── Estadísticas dashboard ─────────────────────────────
    public function getStats() {
        $stats = [];
        $stats['total']      = $this->conn->query("SELECT COUNT(*) AS n FROM incidentes")->fetch_assoc()['n'];
        $stats['abiertos']   = $this->conn->query("SELECT COUNT(*) AS n FROM incidentes i JOIN estados_incidente e ON i.id_estado=e.id_estado WHERE e.nombre_estado='Abierto'")->fetch_assoc()['n'];
        $stats['en_proceso'] = $this->conn->query("SELECT COUNT(*) AS n FROM incidentes i JOIN estados_incidente e ON i.id_estado=e.id_estado WHERE e.nombre_estado='En proceso'")->fetch_assoc()['n'];
        $stats['cerrados']   = $this->conn->query("SELECT COUNT(*) AS n FROM incidentes i JOIN estados_incidente e ON i.id_estado=e.id_estado WHERE e.nombre_estado='Cerrado'")->fetch_assoc()['n'];
        return $stats;
    }
}
