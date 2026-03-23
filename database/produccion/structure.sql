-- Crear base de datos y tabla en phpMyAdmin

-- Ejecutar en phpMyAdmin (SQL tab):

CREATE DATABASE IF NOT EXISTS energia_db;
USE energia_db;

CREATE TABLE IF NOT EXISTS energy_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voltage FLOAT,
    current FLOAT,
    power FLOAT,
    energy FLOAT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
