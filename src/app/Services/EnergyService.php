<?php
// src/app/Services/EnergyService.php

require_once __DIR__ . '/../Models/EnergyData.php';
require_once __DIR__ . '/../Models/Tariff.php';
require_once __DIR__ . '/../Models/DeviceConfig.php';

class EnergyService {
    private $energyData;
    private $tariff;
    private $deviceConfig;

    public function __construct() {
        $this->energyData = new EnergyData();
        $this->tariff = new Tariff();
        $this->deviceConfig = new DeviceConfig();
    }

    /**
     * Guardar lectura desde el ESP32
     */
    public function saveReading($hardwareId, $data) {
        // Buscar por Hardware ID (MAC)
        // 1. Identificar el dispositivo por hardware_id
        $device = $this->deviceConfig->findOrCreateByHardwareId($hardwareId);
        
        if (!$device) {
            return [
                'success' => false, 
                'message' => "Dispositivo [$hardwareId] no pudo ser registrado o encontrado"
            ];
        }

        // Preparar datos
        $voltage      = floatval($data['voltaje'] ?? $data['voltage'] ?? 0);
        $current      = floatval($data['corriente'] ?? $data['current'] ?? 0);
        $power        = floatval($data['potencia'] ?? $data['power'] ?? 0);
        $powerFactor  = floatval($data['factor_potencia'] ?? $data['power_factor'] ?? 0);

        // Potencia aparente S = V * I
        $apparentPower = $voltage * $current;
        $powerActive   = $power;

        // Potencia reactiva Q = sqrt(S² - P²)
        $reactivePower = ($apparentPower > $powerActive)
            ? sqrt(max(0, ($apparentPower * $apparentPower) - ($powerActive * $powerActive)))
            : 0;

        $readingData = [
            'user_id'        => $device['user_id'], // Puede ser NULL si aún no está reclamado
            'voltage'        => $voltage,
            'current'        => $current,
            'power'          => $powerActive,
            'reactive_power' => round($reactivePower, 2),
            'energy'         => floatval($data['energia'] ?? $data['energy'] ?? 0),
            'frequency'      => floatval($data['frecuencia'] ?? $data['frequency'] ?? 0),
            'power_factor'   => $powerFactor,
            'pulse_count'    => intval($data['pulsos_cf'] ?? $data['pulse_count'] ?? 0),
            'relay_status'   => strtoupper($data['relay_estado'] ?? $data['relay_status'] ?? 'OFF'),
        ];

        // Validar datos básicos
        if ($readingData['voltage'] < 0 || $readingData['voltage'] > 500) {
            return ['success' => false, 'message' => 'Voltaje fuera de rango'];
        }

        // Guardar la lectura (EnergyData::saveReading maneja user_id NULL)
        $saved = $this->energyData->saveReading($readingData);

        // Actualizar last_seen usando hardwareId
        $this->deviceConfig->updateLastSeenByHardware($hardwareId);

        // Si tiene dueño, verificar alertas
        if ($device['user_id']) {
            $this->checkAlerts($device, $readingData);
        }

        if ($saved) {
            return ['success' => true, 'message' => 'Datos procesados correctamente'];
        }
        return ['success' => false, 'message' => 'Error al guardar lecturas'];
    }

    /**
     * Obtener datos en tiempo real para el dashboard
     */
    public function getRealTimeData($userId) {
        $latest = $this->energyData->getLatestReading($userId);
        
        if ($latest) {
            $activeTariff = $this->tariff->getActive($userId);
            $rate = $activeTariff ? floatval($activeTariff['rate_per_kwh']) : 0;
            $latest['cost'] = $this->energyData->calculateCost($latest['energy'], $rate);
            $latest['rate_per_kwh'] = $rate;
            $latest['tariff_name'] = $activeTariff['name'] ?? 'Sin tarifa';
        }
        
        return $latest;
    }

    /**
     * Obtener datos para gráficas del dashboard
     */
    public function getChartData($userId, $period = '24h') {
        switch ($period) {
            case '24h':
                return $this->energyData->getHourlyData($userId, 24);
            case '7d':
                return $this->energyData->getDailyData($userId, 7);
            case '30d':
                return $this->energyData->getDailyData($userId, 30);
            default:
                return $this->energyData->getHourlyData($userId, 24);
        }
    }

    /**
     * Obtener estadísticas de consumo
     */
    public function getConsumptionStats($userId) {
        $stats = $this->energyData->getConsumptionStats($userId);
        $activeTariff = $this->tariff->getActive($userId);
        $rate = $activeTariff ? floatval($activeTariff['rate_per_kwh']) : 0;

        // Calcular costos
        $todayEnergy = floatval($stats['today']['today_energy'] ?? 0);
        $monthEnergy = floatval($stats['month']['month_energy'] ?? 0);
        $yesterdayEnergy = floatval($stats['yesterday']['yesterday_energy'] ?? 0);

        $stats['today']['cost'] = $this->energyData->calculateCost($todayEnergy, $rate);
        $stats['month']['cost'] = $this->energyData->calculateCost($monthEnergy, $rate);
        $stats['yesterday']['cost'] = $this->energyData->calculateCost($yesterdayEnergy, $rate);
        $stats['rate'] = $rate;

        // Diferencia porcentual con ayer
        if ($yesterdayEnergy > 0) {
            $stats['today']['diff_percent'] = round((($todayEnergy - $yesterdayEnergy) / $yesterdayEnergy) * 100, 1);
        } else {
            $stats['today']['diff_percent'] = 0;
        }

        return $stats;
    }

    /**
     * Obtener reportes históricos
     */
    public function getHistoricalReports($userId, $startDate, $endDate) {
        $data = $this->energyData->getHistoricalData($userId, $startDate, $endDate);
        $activeTariff = $this->tariff->getActive($userId);
        $rate = $activeTariff ? floatval($activeTariff['rate_per_kwh']) : 0;

        $reports = [];
        foreach ($data as $reading) {
            $reading['cost'] = $this->energyData->calculateCost($reading['energy'], $rate);
            $reports[] = $reading;
        }
        return $reports;
    }

    /**
     * Obtener lecturas en tiempo real (para polling AJAX)
     */
    public function getRealtimeReadings($userId, $count = 20) {
        return $this->energyData->getRealtimeReadings($userId, $count);
    }

    /**
     * Obtener estado del dispositivo
     */
    public function getDeviceStatus($userId) {
        $device = $this->deviceConfig->getByUser($userId);
        if (!$device) {
            return ['online' => false, 'message' => 'No hay dispositivo configurado'];
        }

        $isOnline = false;
        if ($device['last_seen']) {
            $lastSeen = strtotime($device['last_seen']);
            $diff = time() - $lastSeen;
            $isOnline = $diff < 30; // Considerado offline si no envía en 30 seg
        }

        return [
            'online'      => $isOnline,
            'device_name' => $device['device_name'],
            'last_seen'   => $device['last_seen'],
            'api_key'     => $device['api_key'],
        ];
    }

    /**
     * Obtener el estado configurado del relay para un dispositivo
     */
    public function getRelayConfig($apiKey) {
        $device = $this->deviceConfig->validateApiKey($apiKey);
        if (!$device) {
            return null;
        }
        return $device['relay_default']; // Retorna 'ON' o 'OFF'
    }

    /**
     * Vincular un dispositivo compartido al usuario actual usando su API key.
     * El usuario podrá ver los mismos datos en tiempo real que el propietario.
     */
    public function linkSharedDevice($userId, $apiKey) {
        return $this->deviceConfig->linkSharedDevice($userId, $apiKey);
    }

    /**
     * Desvincular un dispositivo compartido
     */
    public function unlinkSharedDevice($userId, $apiKey) {
        return $this->deviceConfig->unlinkSharedDevice($userId, $apiKey);
    }

    /**
     * Obtener dispositivos compartidos con el usuario
     */
    public function getSharedDevices($userId) {
        return $this->deviceConfig->getSharedDevicesByUser($userId);
    }

    /**
     * Obtener datos en tiempo real para el dashboard (propio o compartido)
     */
    public function getRealTimeDataFull($userId) {
        // 1. Intentar datos propios
        $latest = $this->energyData->getLatestReading($userId);

        // 2. Si no hay datos propios, buscar en dispositivos compartidos
        if (!$latest) {
            $shared = $this->deviceConfig->getSharedDevicesByUser($userId);
            foreach ($shared as $s) {
                $device = $this->deviceConfig->validateApiKey($s['api_key']);
                if ($device) {
                    $latest = $this->energyData->getLatestReading($device['user_id']);
                    if ($latest) break;
                }
            }
        }

        if ($latest) {
            $activeTariff = $this->tariff->getActive($userId);
            $rate = $activeTariff ? floatval($activeTariff['rate_per_kwh']) : 0;
            $latest['cost'] = $this->energyData->calculateCost($latest['energy'], $rate);
            $latest['rate_per_kwh'] = $rate;
            $latest['tariff_name'] = $activeTariff['name'] ?? 'Sin tarifa';

            // Costo en tiempo real: potencia activa estimada en pesos/hora
            $powerKW = ($latest['power'] ?? 0) / 1000;
            $latest['realtime_cost_hour'] = round($powerKW * $rate, 2);
        }

        return $latest;
    }

    /**
     * Obtener el consumo promedio por hora del día para identificar picos
     */
    public function getPeakHoursUsage($userId) {
        return $this->energyData->getUsageByHourOfDay($userId);
    }

    /**
     * Obtener reporte de consumo entre fechas
     */
    public function getConsumptionReport($userId, $start, $end) {
        $data = $this->energyData->getRangeData($userId, $start, $end);
        $activeTariff = $this->tariff->getActive($userId);
        $rate = $activeTariff ? floatval($activeTariff['rate_per_kwh']) : 0;

        foreach ($data as &$day) {
            $day['cost'] = $this->energyData->calculateCost($day['daily_energy'], $rate);
        }
        return $data;
    }

    /**
     * Verificar alertas de consumo
     */
    private function checkAlerts($device, $data) {
        try {
            $db = Database::getConnection();

            // Sobrecorriente
            if ($data['current'] > $device['max_current']) {
                $stmt = $db->prepare("
                    INSERT INTO alerts (user_id, type, message, severity)
                    VALUES (?, 'overcurrent', ?, 'danger')
                ");
                $msg = "¡Sobrecorriente detectada! Corriente: {$data['current']}A (máx: {$device['max_current']}A)";
                $stmt->execute([$device['user_id'], $msg]);
            }

            // Sobrepotencia
            if ($data['power'] > $device['max_power']) {
                $stmt = $db->prepare("
                    INSERT INTO alerts (user_id, type, message, severity)
                    VALUES (?, 'overpower', ?, 'danger')
                ");
                $msg = "¡Sobrepotencia detectada! Potencia: {$data['power']}W (máx: {$device['max_power']}W)";
                $stmt->execute([$device['user_id'], $msg]);
            }
        } catch (Exception $e) {
            error_log("Error checking alerts: " . $e->getMessage());
        }
    }
}