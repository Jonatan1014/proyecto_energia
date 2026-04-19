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
                    (user_id, hardware_id, voltage, current_val, power, reactive_power, energy, frequency, power_factor, pulse_count, relay_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['user_id'] ?? null,
                $data['hardware_id'] ?? null,
                $data['voltage'],
                $data['current'],
                $data['power'],
                $data['reactive_power'] ?? 0,
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
                // Buscar la lectura más reciente de cualquier dispositivo vinculado al usuario
                $stmt = $this->pdo->prepare("
                    SELECT er.* 
                    FROM energy_readings er
                    JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                    WHERE ud.user_id = ?
                    ORDER BY er.timestamp DESC LIMIT 1
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
                SELECT er.id, er.voltage, er.current_val, er.power, er.energy, er.frequency, 
                       er.power_factor, er.pulse_count, er.relay_status, er.timestamp
                FROM energy_readings er
                JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                WHERE ud.user_id = ? AND er.timestamp BETWEEN ? AND ?
                ORDER BY er.timestamp ASC
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
                    DATE_FORMAT(er.timestamp, '%Y-%m-%d %H:00:00') as hora,
                    ROUND(AVG(er.voltage), 1) as avg_voltage,
                    ROUND(AVG(er.current_val), 3) as avg_current,
                    ROUND(AVG(er.power), 1) as avg_power,
                    ROUND(MAX(er.energy), 4) as max_energy,
                    ROUND(AVG(er.frequency), 1) as avg_frequency,
                    ROUND(AVG(er.power_factor), 2) as avg_pf,
                    ROUND(SUM(er.pulse_count), 0) as total_pulses
                FROM energy_readings er
                JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                WHERE ud.user_id = ? 
                  AND er.timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
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
                    DATE(er.timestamp) as fecha,
                    ROUND(AVG(er.voltage), 1) as avg_voltage,
                    ROUND(AVG(er.current_val), 3) as avg_current,
                    ROUND(MAX(er.energy) - MIN(er.energy), 4) as daily_energy,
                    ROUND(AVG(er.power), 1) as avg_power
                FROM energy_readings er
                JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                WHERE ud.user_id = ? AND er.timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
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
     * Obtener estadísticas de consumo del usuario (Hoy, Ayer, Mes)
     */
    public function getConsumptionStats($userId) {
        try {
            $stats = [];

            // Hoy
            $stmt = $this->pdo->prepare("
                SELECT 
                    SUM(e_diff) as today_energy,
                    AVG(a_power) as avg_power,
                    MAX(m_power) as max_power,
                    AVG(a_volt) as avg_voltage,
                    AVG(a_curr) as avg_current
                FROM (
                    SELECT 
                        MAX(er.energy) - MIN(er.energy) as e_diff,
                        AVG(er.power) as a_power,
                        MAX(er.power) as m_power,
                        AVG(er.voltage) as a_volt,
                        AVG(er.current_val) as a_curr
                    FROM energy_readings er
                    JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                    WHERE ud.user_id = ? AND DATE(er.timestamp) = CURDATE()
                    GROUP BY er.hardware_id
                ) as t
            ");
            $stmt->execute([$userId]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['today'] = $res ?: ['today_energy' => 0, 'avg_power' => 0, 'max_power' => 0, 'avg_voltage' => 0, 'avg_current' => 0];

            // Ayer
            $stmt = $this->pdo->prepare("
                SELECT SUM(e_diff) as yesterday_energy FROM (
                    SELECT MAX(er.energy) - MIN(er.energy) as e_diff
                    FROM energy_readings er
                    JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                    WHERE ud.user_id = ? AND DATE(er.timestamp) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                    GROUP BY er.hardware_id
                ) as y
            ");
            $stmt->execute([$userId]);
            $stats['yesterday'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Mes
            $stmt = $this->pdo->prepare("
                SELECT SUM(e_diff) as month_energy, AVG(a_power) as avg_power FROM (
                    SELECT 
                        MAX(er.energy) - MIN(er.energy) as e_diff,
                        AVG(er.power) as a_power
                    FROM energy_readings er
                    JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                    WHERE ud.user_id = ? AND MONTH(er.timestamp) = MONTH(NOW()) AND YEAR(er.timestamp) = YEAR(NOW())
                    GROUP BY er.hardware_id
                ) as m
            ");
            $stmt->execute([$userId]);
            $stats['month'] = $stmt->fetch(PDO::FETCH_ASSOC);

            return $stats;
        } catch (Exception $e) {
            error_log("Error getting consumption stats: " . $e->getMessage());
            return [
                'today' => ['today_energy' => 0, 'avg_power' => 0, 'max_power' => 0, 'avg_voltage' => 0, 'avg_current' => 0],
                'month' => ['month_energy' => 0, 'avg_power' => 0],
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
                SELECT er.voltage, er.current_val, er.power, er.reactive_power, er.energy, er.frequency, 
                       er.power_factor, er.relay_status, er.timestamp
                FROM energy_readings er
                JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                WHERE ud.user_id = ?
                ORDER BY er.timestamp DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, (int)$count]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_reverse($data);
        } catch (Exception $e) {
            error_log("Error getting realtime readings: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener el consumo promedio por hora del día (0-23)
     */
    public function getUsageByHourOfDay($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    HOUR(er.timestamp) as hora,
                    ROUND(AVG(er.power), 1) as avg_power
                FROM energy_readings er
                JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                WHERE ud.user_id = ?
                GROUP BY hora
                ORDER BY hora ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting usage by hour: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener datos agrupados por día en un rango
     */
    public function getRangeData($userId, $startDate, $endDate) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    er.f as fecha,
                    ROUND(SUM(er.e_diff), 4) as daily_energy,
                    ROUND(AVG(er.a_power), 1) as avg_power
                FROM (
                    SELECT 
                        hardware_id,
                        DATE(timestamp) as f,
                        MAX(energy) - MIN(energy) as e_diff,
                        AVG(power) as a_power
                    FROM energy_readings
                    WHERE DATE(timestamp) BETWEEN ? AND ?
                    GROUP BY hardware_id, f
                ) as er
                JOIN user_devices ud ON er.hardware_id = ud.hardware_id
                WHERE ud.user_id = ?
                GROUP BY fecha
                ORDER BY fecha ASC
            ");
            $stmt->execute([$startDate, $endDate, $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting range data: " . $e->getMessage());
            return [];
        }
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

    /**
     * Calcular costo de energía
     */
    public function calculateCost($energy, $rate) {
        return round($energy * $rate, 2);
    }

    /**
     * Eliminar todo el historial de lecturas de un dispositivo
     * Esto se usa cuando el usuario resetea el contador de kWh a cero (reset_energy)
     * para que las estadísticas (Max - Min) no calculen consumo incorrecto.
     */
    public function purgeDeviceReadings($hardwareId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM energy_readings WHERE hardware_id = ?");
            $stmt->execute([$hardwareId]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Error purging device readings: " . $e->getMessage());
            return 0;
        }
    }
}