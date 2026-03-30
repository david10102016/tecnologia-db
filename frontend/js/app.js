// ============================================================
// SAFETRACK — JS global
// Funciones compartidas entre todas las páginas
// ============================================================

// CAMBIAR: ruta a la API según tu entorno
// Local XAMPP:  '../backend/api/index.php'
// Render nube:  'https://TU-APP.onrender.com/api/index.php'
const API = window.location.hostname === 'localhost'
    ? '../backend/api/index.php'
    : 'https://david-code.onrender.com/backend/api/index.php';

// ── Fetch helper ──────────────────────────────────────────
async function api(action, data = {}) {
    const res = await fetch(API, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ action, ...data })
    });
    return res.json();
}

// ── Verificar sesión ──────────────────────────────────────
async function checkSesion() {
    try {
        const data = await api('sesion');
        if (!data.autenticado) {
            window.location.href = 'login.html';
            return null;
        }
        return data.usuario;
    } catch {
        window.location.href = 'login.html';
        return null;
    }
}

// ── Cargar info de usuario en sidebar ────────────────────
function cargarUsuarioSidebar(usuario) {
    const avatarEl = document.getElementById('userAvatar');
    const nameEl   = document.getElementById('userName');
    const roleEl   = document.getElementById('userRole');
    if (avatarEl) avatarEl.textContent = (usuario.nombre || usuario.username)[0].toUpperCase();
    if (nameEl)   nameEl.textContent   = usuario.nombre || usuario.username;
    if (roleEl)   roleEl.textContent   = usuario.rol;
}

// ── Logout ────────────────────────────────────────────────
async function logout() {
    await api('logout');
    window.location.href = 'login.html';
}

// ── Sidebar toggle (móvil) ────────────────────────────────
function initSidebar() {
    const toggle  = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    if (!toggle) return;
    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    });
}

// ── Badge de estado ───────────────────────────────────────
function badgeEstado(estado) {
    const map = {
        'Abierto':    'badge-abierto',
        'En proceso': 'badge-proceso',
        'Cerrado':    'badge-cerrado'
    };
    return `<span class="badge ${map[estado] || ''}">${estado}</span>`;
}

// ── Badge de gravedad ─────────────────────────────────────
function badgeGravedad(gravedad) {
    const map = {
        'Leve':     'badge-leve',
        'Moderada': 'badge-moderada',
        'Alta':     'badge-alta',
        'Crítica':  'badge-critica'
    };
    return `<span class="badge ${map[gravedad] || ''}">${gravedad}</span>`;
}

// ── Formatear fecha ───────────────────────────────────────
function formatFecha(fecha) {
    if (!fecha) return '—';
    const d = new Date(fecha);
    return d.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' })
         + ' ' + d.toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });
}

// ── Mostrar alerta ────────────────────────────────────────
function showAlert(id, msg, type = 'error') {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = msg;
    el.className   = `alert ${type} show`;
    setTimeout(() => el.className = 'alert', 4000);
}

// ── Inicializar sidebar en todas las páginas ──────────────
document.addEventListener('DOMContentLoaded', initSidebar);
