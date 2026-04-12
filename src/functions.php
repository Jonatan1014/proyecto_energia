<?php
// src/functions.php - Funciones helper globales

/**
 * Debug y die
 */
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die();
}

/**
 * Redireccionar
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Generar URL con base
 */
function url($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Generar URL para assets
 */
function asset_url($path) {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Generar URL para uploads
 */
function upload_url($path) {
    return BASE_URL . '/uploads/' . ltrim($path, '/');
}

/**
 * Formatear moneda COP
 */
function format_cop($value) {
    return '$ ' . number_format($value, 0, ',', '.');
}

/**
 * Formatear fecha para mostrar
 */
function format_date($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Tiempo relativo (hace X minutos/horas)
 */
function time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return "hace " . $diff->y . " año" . ($diff->y > 1 ? "s" : "");
    if ($diff->m > 0) return "hace " . $diff->m . " mes" . ($diff->m > 1 ? "es" : "");
    if ($diff->d > 0) return "hace " . $diff->d . " día" . ($diff->d > 1 ? "s" : "");
    if ($diff->h > 0) return "hace " . $diff->h . " hora" . ($diff->h > 1 ? "s" : "");
    if ($diff->i > 0) return "hace " . $diff->i . " min";
    return "ahora";
}