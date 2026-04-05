<?php
// src/functions.php - Funciones helper globales

// Funciones de utilidad para el proyecto MedidorEnergia

function dd($var) {
    var_dump($var);
    die();
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function url($path) {
    return BASE_URL . ltrim($path, '/');
}

function asset_url($path) {
    return rtrim(ASSETS_URL, '/') . '/' . ltrim($path, '/');
}

function upload_url($path) {
    return BASE_URL . ltrim($path, '/');
}

// Agregar más funciones según sea necesario