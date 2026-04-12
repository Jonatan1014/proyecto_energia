<?php
// src/app/Models/Tariff.php

require_once __DIR__ . '/../../config/database.php';

class Tariff {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Guardar nueva tarifa
     */
    public function save($userId, $name, $rate, $startDate = null, $endDate = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO tariffs (user_id, name, rate_per_kwh, start_date, end_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$userId, $name, $rate, $startDate, $endDate]);
        } catch (Exception $e) {
            error_log("Error saving tariff: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tarifa activa del usuario
     */
    public function getActive($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM tariffs 
                WHERE user_id = ? AND is_active = 1
                  AND (start_date IS NULL OR start_date <= CURDATE())
                  AND (end_date IS NULL OR end_date >= CURDATE())
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active tariff: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todas las tarifas del usuario
     */
    public function getAllByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM tariffs WHERE user_id = ? ORDER BY is_active DESC, id DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting tariffs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tarifa por ID
     */
    public function findById($id, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM tariffs WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error finding tariff: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar tarifa
     */
    public function update($id, $userId, $name, $rate, $isActive, $startDate = null, $endDate = null) {
        try {
            // Si esta tarifa se activa, desactivar las demás
            if ($isActive) {
                $this->deactivateAll($userId, $id);
            }

            $stmt = $this->pdo->prepare("
                UPDATE tariffs 
                SET name = ?, rate_per_kwh = ?, is_active = ?, start_date = ?, end_date = ?
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$name, $rate, $isActive, $startDate, $endDate, $id, $userId]);
        } catch (Exception $e) {
            error_log("Error updating tariff: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desactivar todas las tarifas excepto una
     */
    private function deactivateAll($userId, $exceptId = null) {
        try {
            if ($exceptId) {
                $stmt = $this->pdo->prepare("UPDATE tariffs SET is_active = 0 WHERE user_id = ? AND id != ?");
                $stmt->execute([$userId, $exceptId]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE tariffs SET is_active = 0 WHERE user_id = ?");
                $stmt->execute([$userId]);
            }
        } catch (Exception $e) {
            error_log("Error deactivating tariffs: " . $e->getMessage());
        }
    }

    /**
     * Eliminar tarifa
     */
    public function delete($id, $userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM tariffs WHERE id = ? AND user_id = ?");
            return $stmt->execute([$id, $userId]);
        } catch (Exception $e) {
            error_log("Error deleting tariff: " . $e->getMessage());
            return false;
        }
    }
}