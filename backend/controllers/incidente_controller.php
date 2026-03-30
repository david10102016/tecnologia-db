<?php
// ============================================================
// CONTROLADOR DE INCIDENTES
// Maneja CRUD de incidentes y acciones correctivas
// Guarda en MySQL Y Supabase al mismo tiempo
// Si MySQL falla, Supabase actúa como respaldo automático
// ============================================================
require_once __DIR__ . '/../models/mysql_model.php';
require_once __DIR__ . '/../models/supabase_model.php';

class IncidenteController {

    private $mysql;
    private $supabase;

    public function __construct() {
        $this->mysql    = new MySQLModel();
        $this->supabase = new SupabaseModel();
    }

    // ── Obtener incidentes ─────────────────────────────────
    public function listar() {
        // Intenta traer de MySQL primero
        // Si MySQL no está disponible, trae de Supabase
        try {
            $data = $this->mysql->getIncidentes();
            return ['success' => true, 'fuente' => 'mysql', 'data' => $data];
        } catch (Exception $e) {
            error_log("MySQL no disponible al listar: " . $e->getMessage());
            $data = $this->supabase->getIncidentes();
            return ['success' => true, 'fuente' => 'supabase', 'data' => $data];
        }
    }

    public function sinResolver() {
        try {
            $mysql_data = $this->mysql->getIncidentesSinResolver();
        } catch (Exception $e) {
            error_log("MySQL no disponible en sinResolver: " . $e->getMessage());
            $mysql_data = [];
        }
        return [
            'success'  => true,
            'mysql'    => $mysql_data,
            'supabase' => $this->supabase->getIncidentesSinResolver()
        ];
    }

    public function frecuenciaPorTipo() {
        try {
            $mysql_data = $this->mysql->getFrecuenciaPorTipo();
        } catch (Exception $e) {
            error_log("MySQL no disponible en frecuencia: " . $e->getMessage());
            $mysql_data = [];
        }
        return [
            'success'  => true,
            'mysql'    => $mysql_data,
            'supabase' => $this->supabase->getFrecuenciaPorTipo()
        ];
    }

    // ── Registrar incidente ────────────────────────────────
    public function registrar($datos) {
        // Validar campos requeridos
        $requeridos = ['descripcion', 'id_tipo', 'id_area', 'id_gravedad', 'id_usuario_reg'];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                return ['success' => false, 'message' => "El campo $campo es requerido"];
            }
        }

        $id_mysql   = null;
        $mysql_ok   = false;
        $supabase_ok = false;

        // 1. Intentar guardar en MySQL
        // Si falla, no detiene el sistema
        try {
            $id_mysql = $this->mysql->insertarIncidente($datos);
            $mysql_ok = true;
        } catch (Exception $e) {
            error_log("MySQL no disponible al registrar: " . $e->getMessage());
        }

        // 2. Supabase siempre intenta guardar
        // pase lo que pase con MySQL
        try {
            $this->supabase->insertarIncidente($datos);
            $supabase_ok = true;
        } catch (Exception $e) {
            error_log("Supabase no disponible al registrar: " . $e->getMessage());
        }

        // 3. Si ninguno funcionó, reportar error
        if (!$mysql_ok && !$supabase_ok) {
            return ['success' => false, 'message' => 'Error: no se pudo conectar a ninguna base de datos'];
        }

        // 4. Guardar empleados involucrados si MySQL está disponible
        if ($mysql_ok && $id_mysql && !empty($datos['empleados_involucrados'])) {
            foreach ($datos['empleados_involucrados'] as $emp) {
                try {
                    $this->mysql->insertarEmpleadoIncidente(
                        $id_mysql,
                        $emp['id_empleado'],
                        $emp['rol'] ?? 'Involucrado'
                    );
                } catch (Exception $e) {
                    error_log("Error al insertar empleado involucrado: " . $e->getMessage());
                }
            }
        }

        // 5. Informar al frontend exactamente qué pasó
        if ($mysql_ok && $supabase_ok) {
            $mensaje = 'Incidente registrado en ambas bases de datos';
        } elseif ($supabase_ok) {
            $mensaje = 'Incidente registrado en Supabase (MySQL no disponible temporalmente)';
        } else {
            $mensaje = 'Incidente registrado en MySQL (Supabase no disponible temporalmente)';
        }

        return ['success' => true, 'message' => $mensaje, 'id' => $id_mysql];
    }

    // ── Cerrar incidente ───────────────────────────────────
    public function cerrar($id_incidente, $rol_usuario) {
        // Validar que sea supervisor
        // La consigna pide explícitamente que solo supervisores cierren incidentes
        if ($rol_usuario !== 'supervisor') {
            return ['success' => false, 'message' => 'Solo supervisores pueden cerrar incidentes'];
        }

        $mysql_ok    = false;
        $supabase_ok = false;

        // Cerrar en MySQL
        try {
            $this->mysql->cerrarIncidente($id_incidente);
            $mysql_ok = true;
        } catch (Exception $e) {
            error_log("MySQL no disponible al cerrar: " . $e->getMessage());
        }

        // Cerrar en Supabase
        // Supabase además aplica Row Level Security (RLS)
        // que refuerza esta restricción a nivel de base de datos
        try {
            $this->supabase->cerrarIncidente($id_incidente);
            $supabase_ok = true;
        } catch (Exception $e) {
            error_log("Supabase no disponible al cerrar: " . $e->getMessage());
        }

        if (!$mysql_ok && !$supabase_ok) {
            return ['success' => false, 'message' => 'Error al cerrar: sin conexión a las bases de datos'];
        }

        if ($mysql_ok && $supabase_ok) {
            $mensaje = 'Incidente cerrado en ambas bases de datos';
        } elseif ($supabase_ok) {
            $mensaje = 'Incidente cerrado en Supabase (MySQL no disponible temporalmente)';
        } else {
            $mensaje = 'Incidente cerrado en MySQL (Supabase no disponible temporalmente)';
        }

        return ['success' => true, 'message' => $mensaje];
    }

    // ── Acciones correctivas ───────────────────────────────
    public function listarAcciones($id_incidente = null) {
        try {
            $data = $this->mysql->getAcciones($id_incidente);
            return ['success' => true, 'fuente' => 'mysql', 'data' => $data];
        } catch (Exception $e) {
            error_log("MySQL no disponible al listar acciones: " . $e->getMessage());
            $data = $this->supabase->getAcciones();
            return ['success' => true, 'fuente' => 'supabase', 'data' => $data];
        }
    }

    public function registrarAccion($datos) {
        if (empty($datos['id_incidente']) || empty($datos['descripcion'])) {
            return ['success' => false, 'message' => 'Datos incompletos'];
        }

        $mysql_ok    = false;
        $supabase_ok = false;

        try {
            $this->mysql->insertarAccion($datos);
            $mysql_ok = true;
        } catch (Exception $e) {
            error_log("MySQL no disponible al registrar acción: " . $e->getMessage());
        }

        try {
            $this->supabase->insertarAccion($datos);
            $supabase_ok = true;
        } catch (Exception $e) {
            error_log("Supabase no disponible al registrar acción: " . $e->getMessage());
        }

        if (!$mysql_ok && !$supabase_ok) {
            return ['success' => false, 'message' => 'Error: sin conexión a las bases de datos'];
        }

        if ($mysql_ok && $supabase_ok) {
            $mensaje = 'Acción registrada en ambas bases de datos';
        } elseif ($supabase_ok) {
            $mensaje = 'Acción registrada en Supabase (MySQL no disponible temporalmente)';
        } else {
            $mensaje = 'Acción registrada en MySQL (Supabase no disponible temporalmente)';
        }

        return ['success' => true, 'message' => $mensaje];
    }

    // ── Catálogos para los formularios ────────────────────
    public function getCatalogos() {
        try {
            return [
                'success'    => true,
                'fuente'     => 'mysql',
                'areas'      => $this->mysql->getAreas(),
                'tipos'      => $this->mysql->getTipos(),
                'gravedades' => $this->mysql->getGravedades(),
                'estados'    => $this->mysql->getEstados(),
                'empleados'  => $this->mysql->getEmpleados()
            ];
        } catch (Exception $e) {
            // Si MySQL falla, trae catálogos de Supabase
            error_log("MySQL no disponible en catálogos: " . $e->getMessage());
            return [
                'success'    => true,
                'fuente'     => 'supabase',
                'areas'      => $this->supabase->getAreas(),
                'tipos'      => $this->supabase->getTipos(),
                'gravedades' => $this->supabase->getGravedades(),
                'estados'    => [],
                'empleados'  => []
            ];
        }
    }

    // ── Estadísticas para el dashboard ────────────────────
    public function getStats() {
        $mysql_stats = [];
        try {
            $mysql_stats = $this->mysql->getStats();
        } catch (Exception $e) {
            error_log("MySQL no disponible en stats: " . $e->getMessage());
        }

        return [
            'success'    => true,
            'mysql'      => $mysql_stats,
            'supabase'   => $this->supabase->getStats(),
            'frecuencia' => !empty($mysql_stats) ? $this->mysql->getFrecuenciaPorTipo() : $this->supabase->getFrecuenciaPorTipo()
        ];
    }
}