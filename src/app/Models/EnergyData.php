<?php
// src/app/Models/EnergyData.php

require_once __DIR__ . '/../../config/database.php';

class EnergyData {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function saveData($voltage, $current, $power, $energy) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO energy_readings (voltage, current, power, energy) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$voltage, $current, $power, $energy]);
        } catch (Exception $e) {
            error_log("Error saving energy data: " . $e->getMessage());
            return false;
        }
    }

    public function getLatestData() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM energy_readings ORDER BY timestamp DESC LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting latest data: " . $e->getMessage());
            return null;
        }
    }

    public function getHistoricalData($startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM energy_readings WHERE timestamp BETWEEN ? AND ? ORDER BY timestamp ASC");
            $stmt->execute([$startDate, $endDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting historical data: " . $e->getMessage());
            return [];
        }
    }

    public function calculateCost($energy, $rate) {
        return $energy * $rate;
    }
}