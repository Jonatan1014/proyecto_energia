<?php
// src/app/Models/Tariff.php

require_once __DIR__ . '/../../config/database.php';

class Tariff {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function save($rate, $startDate = null, $endDate = null) {
        $stmt = $this->pdo->prepare("INSERT INTO tariffs (rate_per_kwh, start_date, end_date) VALUES (?, ?, ?)");
        return $stmt->execute([$rate, $startDate, $endDate]);
    }

    public function getActive() {
        $stmt = $this->pdo->query("SELECT * FROM tariffs WHERE (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY id DESC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM tariffs ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $rate, $startDate, $endDate) {
        $stmt = $this->pdo->prepare("UPDATE tariffs SET rate_per_kwh = ?, start_date = ?, end_date = ? WHERE id = ?");
        return $stmt->execute([$rate, $startDate, $endDate, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM tariffs WHERE id = ?");
        return $stmt->execute([$id]);
    }
}