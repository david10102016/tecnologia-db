<?php
// ============================================================
// API REST — PUNTO DE ENTRADA
// Todas las peticiones del frontend llegan aquí
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  // CAMBIAR en producción por tu dominio
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/auth_controller.php';
require_once __DIR__ . '/../controllers/incidente_controller.php';

// Leer body JSON
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $_GET['action'] ?? '';

$auth       = new AuthController();
$incidentes = new IncidenteController();

// ── Rutas públicas (no requieren sesión) ───────────────────
if ($action === 'login') {
    echo json_encode($auth->login(
        $body['username'] ?? '',
        $body['password'] ?? '',
        $body['motor']    ?? 'mysql' // CAMBIAR a 'supabase' si está desplegado en Render
    ));
    exit;
}

if ($action === 'logout') {
    echo json_encode($auth->logout());
    exit;
}

// ── Verificar sesión para rutas protegidas ─────────────────
$sesion = $auth->verificarSesion();
if (!$sesion['autenticado']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$rol        = $sesion['usuario']['rol'];
$id_usuario = $sesion['usuario']['id'];

// ── Rutas protegidas ───────────────────────────────────────
switch ($action) {

    // Sesión activa
    case 'sesion':
        echo json_encode($sesion);
        break;

    // Catálogos
    case 'catalogos':
        echo json_encode($incidentes->getCatalogos());
        break;

    // Dashboard
    case 'stats':
        echo json_encode($incidentes->getStats());
        break;

    // Incidentes
    case 'listar_incidentes':
        echo json_encode($incidentes->listar());
        break;

    case 'sin_resolver':
        echo json_encode($incidentes->sinResolver());
        break;

    case 'frecuencia':
        echo json_encode($incidentes->frecuenciaPorTipo());
        break;

    case 'registrar_incidente':
        $body['id_usuario_reg'] = $id_usuario;
        echo json_encode($incidentes->registrar($body));
        break;

    case 'cerrar_incidente':
        // Solo supervisores — validado también en el controller
        echo json_encode($incidentes->cerrar($body['id_incidente'] ?? 0, $rol));
        break;

    // Acciones correctivas
    case 'listar_acciones':
        echo json_encode($incidentes->listarAcciones($body['id_incidente'] ?? null));
        break;

    case 'registrar_accion':
        $body['id_usuario'] = $id_usuario;
        echo json_encode($incidentes->registrarAccion($body));
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Acción no encontrada']);
        break;
}
