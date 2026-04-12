<?php
// src/app/Models/EnergyData.php

require_once __DIR__ . '/../../config/database.php';

class EnergyData {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Guardar lectura completa del PZEM-004T
     */
    public function saveReading($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO energy_readings 
                    (user_id, voltage, current_val, power, energy, frequency, power_factor, pulse_count, relay_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['user_id'] ?? null,
                $data['voltage'],
                $data['current'],
                $data['power'],
                $data['energy'],
                $data['frequency'] ?? null,
                $data['power_factor'] ?? null,
                $data['pulse_count'] ?? 0,
                $data['relay_status'] ?? 'OFF'
            ]);
        } catch (Exception $e) {
            error_log("Error saving energy reading: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener la lectura más reciente de un usuario
     */
    public function getLatestReading($userId = null) {
        try {
            if ($userId) {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM energy_readings 
                    WHERE user_id = ? 
                    ORDER BY timestamp DESC LIMIT 1
                ");
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->pdo->query("
                    SELECT * FROM energy_readings 
                    ORDER BY timestamp DESC LIMIT 1
                ");
            }
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting latest reading: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener lecturas históricas por rango de fecha
     */
    public function getHistoricalData($userId, $startDate, $endDate, $limit = 500) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, voltage, current_val, power, energy, frequency, 
                       power_factor, pulse_count, relay_status, timestamp
                FROM energy_readings 
                WHERE user_id = ? AND timestamp BETWEEN ? AND ?
                ORDER BY timestamp ASC
                LIMIT ?
            ");
            $stmt->execute([$userId, $startDate, $endDate, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting historical data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener datos agrupados por hora para gráficas (últimas 24h)
     */
    public function getHourlyData($userId, $hours = 24) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hora,
                    ROUND(AVG(voltage), 1) as avg_voltage,
                    ROUND(AVG(current_val), 3) as avg_current,
                    ROUND(AVG(power), 1) as avg_power,
                    ROUND(MAX(energy), 4) as max_energy,
                    ROUND(AVG(frequency), 1) as avg_frequency,
                    ROUND(AVG(power_factor), 2) as avg_pf,
                    COUNT(*) as lecturas
                FROM energy_readings
                WHERE user_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY hora
                ORDER BY hora ASC
            ");
            $stmt->execute([$userId, $hours]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting hourly data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener datos agrupados por día para gráficas (últimos N días)
     */
    public function getDailyData($userId, $days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    DATE(timestamp) as fecha,
                    ROUND(AVG(voltage), 1) as avg_voltage,
                    ROUND(MAX(current_val), 3) as max_current,
                    ROUND(AVG(power), 1) as avg_power,
                    ROUND(MAX(power), 1) as max_power,
                    ROUND(MAX(energy) - MIN(energy), 4) as daily_energy,
                    COUNT(*) as lecturas
                FROM energy_readings
                WHERE user_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY fecha
                ORDER BY fecha ASC
            ");
            $stmt->execute([$userId, $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting daily data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de consumo del usuario
     */
    public function getConsumptionStats($userId) {
        try {
            $stats = [];

            // Consumo de hoy
            $stmt = $this->pdo->prepare("
                SELECT 
                    ROUND(MAX(energy) - MIN(energy), 4) as today_energy,
                    ROUND(AVG(power), 1) as avg_power,
                    ROUND(MAX(power), 1) as max_power,
                    ROUND(AVG(voltage), 1) as avg_voltage,
                    ROUND(AVG(current_val), 3) as avg_current,
                    COUNT(*) as readings_count
                FROM energy_readings
                WHERE user_id = ? AND DATE(timestamp) = CURDATE()
            ");
            $stmt->execute([$userId]);
            $stats['today'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Consumo del mes
            $stmt = $this->pdo->prepare("
                SELECT 
                    ROUND(MAX(energy) - MIN(energy), 4) as month_energy,
                    ROUND(AVG(power), 1) as avg_power,
                    ROUND(MAX(power), 1) as max_power
                FROM energy_readings
                WHERE user_id = ? AND MONTH(timestamp) = MONTH(CURDATE()) AND YEAR(timestamp) = YEAR(CURDATE())
            ");
            $stmt->execute([$userId]);
            $stats['month'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Consumo de ayer (para comparación)
            $stmt = $this->pdo->prepare("
                SELECT 
                    ROUND(MAX(energy) - MIN(energy), 4) as yesterday_energy
                FROM energy_readings
                WHERE user_id = ? AND DATE(timestamp) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            ");
            $stmt->execute([$userId]);
            $stats['yesterday'] = $stmt->fetch(PDO::FETCH_ASSOC);

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting consumption stats: " . $e->getMessage());
            return [
                'today' => ['today_energy' => 0, 'avg_power' => 0, 'max_power' => 0, 'avg_voltage' => 0, 'avg_current' => 0, 'readings_count' => 0],
                'month' => ['month_energy' => 0, 'avg_power' => 0, 'max_power' => 0],
                'yesterday' => ['yesterday_energy' => 0]
            ];
        }
    }

    /**
     * Obtener las últimas N lecturas en tiempo real
     */
    public function getRealtimeReadings($userId, $count = 20) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT voltage, current_val, power, energy, frequency, 
                       power_factor, relay_status, timestamp
                FROM energy_readings
                WHERE user_id = ?
                ORDER BY timestamp DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $count]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_reverse($data); // Orden cronológico
        } catch (Exception $e) {
            error_log("Error getting realtime readings: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcular costo de energía
     */
    public function calculateCost($energy, $rate) {
        return round($energy * $rate, 2);
    }

    /**
     * Purgar lecturas antiguas (más de 90 días) para mantenimiento
     */
    public function purgeOldReadings($days = 90) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM energy_readings 
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Error purging old readings: " . $e->getMessage());
            return 0;
        }
    }
}