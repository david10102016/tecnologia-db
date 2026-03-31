<?php
// ============================================================
// CONFIGURACIÓN DE BASES DE DATOS
// Sistema de Registro de Incidentes de Seguridad Laboral
// ============================================================

define('MYSQL_HOST',   'localhost');       // CAMBIAR si usas Railway
define('MYSQL_USER',   'root');            // CAMBIAR si usas Railway
define('MYSQL_PASS',   '');               // CAMBIAR si usas Railway
define('MYSQL_DB',     'incidentes_db');  // CAMBIAR si usas Railway
define('MYSQL_PORT',    3306);            // CAMBIAR si usas Railway

define('SUPABASE_URL', 'https://vitmgdsgmilmjcattjvv.supabase.co');
define('SUPABASE_KEY', 'sb_publishable_SgZ-H-V113BqSLbJDgDtcQ_RcVnSI7y');

function conectarMySQL() {
    try {
        $conn = @new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT);
        if ($conn->connect_error) {
            return null;
        }
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (Exception $e) {
        return null;
    }
}

function supabaseRequest($tabla, $method = 'GET', $data = null, $filtro = '') {
    $url = SUPABASE_URL . '/rest/v1/' . $tabla . $filtro;
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: '               . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: '               . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=minimal'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: '               . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ]);
    }

    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}