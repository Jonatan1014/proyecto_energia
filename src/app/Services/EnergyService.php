<?php
// src/app/Services/EnergyService.php

require_once __DIR__ . '/../Models/EnergyData.php';
require_once __DIR__ . '/../Models/Tariff.php';

class EnergyService {
    private $energyData;
    private $tariff;

    public function __construct() {
        $this->energyData = new EnergyData();
        $this->tariff = new Tariff();
    }

    public function getRealTimeData() {
        $data = $this->energyData->getLatestData();
        if ($data) {
            $activeTariff = $this->tariff->getActive();
            if ($activeTariff) {
                $data['cost'] = $this->energyData->calculateCost($data['energy'], $activeTariff['rate_per_kwh']);
            } else {
                $data['cost'] = 0;
            }
        }
        return $data;
    }

    public function getHistoricalReports($startDate, $endDate) {
        $data = $this->energyData->getHistoricalData($startDate, $endDate);
        $activeTariff = $this->tariff->getActive();
        $rate = $activeTariff ? $activeTariff['rate_per_kwh'] : 0;

        $reports = [];
        foreach ($data as $reading) {
            $reports[] = [
                'timestamp' => $reading['timestamp'],
                'voltage' => $reading['voltage'],
                'current' => $reading['current'],
                'power' => $reading['power'],
                'energy' => $reading['energy'],
                'cost' => $this->energyData->calculateCost($reading['energy'], $rate)
            ];
        }
        return $reports;
    }

    public function checkOverload($current, $threshold) {
        return $current > $threshold;
    }

    public function saveData($voltage, $current, $power, $energy) {
        return $this->energyData->saveData($voltage, $current, $power, $energy);
    }
}