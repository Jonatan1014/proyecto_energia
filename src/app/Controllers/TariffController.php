<?php
// src/app/Controllers/TariffController.php

require_once __DIR__ . '/../Models/Tariff.php';

class TariffController {
    private $tariff;

    public function __construct() {
        $this->tariff = new Tariff();
    }

    public function index($request, $response) {
        $tariffs = $this->tariff->getAll();
        // Renderizar vista
        ob_start();
        include __DIR__ . '/../Views/tariff.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    public function create($request, $response) {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $rate = $data['rate'] ?? 0;
            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;
            $this->tariff->save($rate, $startDate, $endDate);
            return $response->withHeader('Location', '/tariffs')->withStatus(302);
        }
        // Mostrar formulario
        ob_start();
        include __DIR__ . '/../Views/tariff_form.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    public function update($request, $response, $args) {
        $id = $args['id'];
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $rate = $data['rate'] ?? 0;
            $startDate = $data['start_date'] ?? null;
            $endDate = $data['end_date'] ?? null;
            $this->tariff->update($id, $rate, $startDate, $endDate);
            return $response->withHeader('Location', '/tariffs')->withStatus(302);
        }
        // Mostrar formulario con datos existentes
        // (Implementar lógica para obtener tarifa por ID)
        ob_start();
        include __DIR__ . '/../Views/tariff_form.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    public function delete($request, $response, $args) {
        $id = $args['id'];
        $this->tariff->delete($id);
        return $response->withHeader('Location', '/tariffs')->withStatus(302);
    }
}