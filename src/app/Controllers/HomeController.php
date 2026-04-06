<?php
// src/app/Controllers/HomeController.php

require_once __DIR__ . '/../Services/EnergyService.php';

class HomeController {
    private $energyService;

    public function __construct() {
        $this->energyService = new EnergyService();
    }

    public function index() {
        $data = $this->energyService->getRealTimeData();
        ob_start();
        include __DIR__ . '/../Views/home.php';
        $html = ob_get_clean();
        echo $html;
    }
}