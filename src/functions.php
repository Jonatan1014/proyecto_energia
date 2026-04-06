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
    return BASE_URL . $path;
}

// Agregar más funciones según sea necesario