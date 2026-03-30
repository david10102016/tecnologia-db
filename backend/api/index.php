<?php
// ============================================================
// API REST — PUNTO DE ENTRADA
// Todas las peticiones del frontend llegan aquí
// ============================================================
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../controllers/auth_controller.php';
require_once __DIR__ . '/../controllers/incidente_controller.php';

// Leer body JSON
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? $_GET['action'] ?? '';

// Leer token del header o del body
$headers     = getallheaders();
$token       = $headers['Authorization'] ?? $body['token'] ?? '';
$token       = str_replace('Bearer ', '', $token);

$auth       = new AuthController();
$incidentes = new IncidenteController();

// ── Rutas públicas (no requieren token) ───────────────────
if ($action === 'login') {
    echo json_encode($auth->login(
        $body['username'] ?? '',
        $body['password'] ?? '',
        $body['motor'] ?? (getenv('MOTOR') ?: 'mysql')
    ));
    exit;
}

if ($action === 'logout') {
    echo json_encode($auth->logout());
    exit;
}

// ── Verificar token para rutas protegidas ──────────────────
$sesion = $auth->verificarSesion($token);
if (!$sesion['autenticado']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$rol        = $sesion['usuario']['rol'];
$id_usuario = $sesion['usuario']['id'];

// ── Rutas protegidas ───────────────────────────────────────
switch ($action) {

    case 'sesion':
        echo json_encode($sesion);
        break;

    case 'catalogos':
        echo json_encode($incidentes->getCatalogos());
        break;

    case 'stats':
        echo json_encode($incidentes->getStats());
        break;

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
        echo json_encode($incidentes->cerrar($body['id_incidente'] ?? 0, $rol));
        break;

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