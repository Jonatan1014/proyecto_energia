<?php
// src/app/Models/Cuenta.php

require_once __DIR__ . '/../../config/database.php';

class Cuenta {

    /**
     * Obtener todas las cuentas activas de un usuario
     */
    public static function getAllForUser($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM cuentas
            WHERE user_id = ? AND is_active = 1
            ORDER BY FIELD(tipo, 'banco', 'efectivo', 'ahorro', 'billetera_digital'), nombre ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener el resumen de saldos por tipo y total
     */
    public static function getBalancesresumen($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT tipo, SUM(saldo_actual) as total_saldo
            FROM cuentas 
            WHERE user_id = ? AND is_active = 1
            GROUP BY tipo
        ");
        $stmt->execute([$userId]);
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resumen = [
            'total' => 0,
            'banco' => 0,
            'efectivo' => 0,
            'ahorro' => 0,
            'billetera_digital' => 0
        ];
        
        foreach($tipos as $t) {
            $total = floatval($t['total_saldo']);
            $resumen[$t['tipo']] = $total;
            $resumen['total'] += $total;
        }
        
        return $resumen;
    }

    /**
     * Encontrar una cuenta por ID
     */
    public static function findById($id) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM cuentas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nueva cuenta
     */
    public static function create($data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO cuentas (user_id, nombre, tipo, saldo_actual, color, icono)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['user_id'],
            $data['nombre'],
            $data['tipo'] ?? 'banco',
            $data['saldo_actual'] ?? 0,
            $data['color'] ?? '#4caf50',
            $data['icono'] ?? 'fas fa-wallet'
        ]);
        return $db->lastInsertId();
    }

    /**
     * Modificar saldo de una cuenta (sumar o restar)
     */
    public static function updateSaldo($id, $monto) {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE cuentas SET saldo_actual = saldo_actual + ? WHERE id = ?");
        return $stmt->execute([$monto, $id]);
    }

    /**
     * Actualizar cuenta
     */
    public static function update($id, $data) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE cuentas SET 
                nombre = ?, tipo = ?, saldo_actual = ?, color = ?, icono = ?, is_active = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['tipo'],
            $data['saldo_actual'],
            $data['color'],
            $data['icono'],
            $data['is_active'] ?? 1,
            $id
        ]);
    }
}
