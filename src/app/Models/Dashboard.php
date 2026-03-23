<?php
// src/app/Models/Dashboard.php

require_once __DIR__ . '/../../config/database.php';

class Dashboard {
    
    /**
     * Obtener estadísticas financieras del usuario
     */
    public static function getStats($userId) {
        $db = Database::getConnection();
        $stats = [];

        try {
            // Balance del mes actual
            $stmt = $db->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) as ingresos_mes,
                    COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END), 0) as gastos_mes
                FROM transacciones 
                WHERE user_id = ? AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
            ");
            $stmt->execute([$userId]);
            $balance = $stmt->fetch();
            $stats['ingresos_mes'] = $balance['ingresos_mes'];
            $stats['gastos_mes'] = $balance['gastos_mes'];
            $stats['balance_mes'] = $balance['ingresos_mes'] - $balance['gastos_mes'];

            // Total ahorrado
            $stmt = $db->prepare("SELECT COALESCE(SUM(monto_actual), 0) as total FROM metas_ahorro WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$userId]);
            $stats['total_ahorrado'] = $stmt->fetch()['total'];

            // Tarjetas activas
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM tarjetas_credito WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$userId]);
            $stats['total_tarjetas'] = $stmt->fetch()['total'];

            // Gastos recurrentes activos
            $stmt = $db->prepare("SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as total_monto FROM gastos_recurrentes WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$userId]);
            $recurrentes = $stmt->fetch();
            $stats['total_recurrentes'] = $recurrentes['total'];
            $stats['monto_recurrentes'] = $recurrentes['total_monto'];

            // Alertas sin leer
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM alertas WHERE user_id = ? AND leida = 0");
            $stmt->execute([$userId]);
            $stats['alertas_no_leidas'] = $stmt->fetch()['total'];

            // Metas activas
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM metas_ahorro WHERE user_id = ? AND is_active = 1");
            $stmt->execute([$userId]);
            $stats['total_metas'] = $stmt->fetch()['total'];

            // Gastos por categoría este mes
            $stmt = $db->prepare("
                SELECT c.nombre, c.color, c.icono, SUM(t.monto) as total
                FROM transacciones t
                LEFT JOIN categorias c ON t.categoria_id = c.id
                WHERE t.user_id = ? AND t.tipo = 'gasto' 
                  AND MONTH(t.fecha) = MONTH(CURDATE()) AND YEAR(t.fecha) = YEAR(CURDATE())
                GROUP BY c.id, c.nombre, c.color, c.icono
                ORDER BY total DESC
                LIMIT 8
            ");
            $stmt->execute([$userId]);
            $stats['gastos_por_categoria'] = $stmt->fetchAll();

            // Tendencia diaria del mes actual
            $stmt = $db->prepare("
                SELECT 
                    DAY(fecha) as dia,
                    SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos
                FROM transacciones
                WHERE user_id = ? AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
                GROUP BY dia
                ORDER BY dia ASC
            ");
            $stmt->execute([$userId]);
            $stats['tendencia_mensual'] = $stmt->fetchAll();

            // Últimas transacciones
            $stmt = $db->prepare("
                SELECT t.*, c.nombre as categoria_nombre, c.icono as categoria_icono, c.color as categoria_color,
                       cu.nombre as cuenta_nombre, cu.icono as cuenta_icono
                FROM transacciones t
                LEFT JOIN categorias c ON t.categoria_id = c.id
                LEFT JOIN cuentas cu ON t.cuenta_id = cu.id
                WHERE t.user_id = ?
                ORDER BY t.fecha DESC, t.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $stats['ultimas_transacciones'] = $stmt->fetchAll();

            // Próximos pagos recurrentes (30 días + Vencidos)
            $stmt = $db->prepare("
                SELECT gr.*, c.nombre as categoria_nombre, c.icono as categoria_icono, c.color as categoria_color
                FROM gastos_recurrentes gr
                LEFT JOIN categorias c ON gr.categoria_id = c.id
                WHERE gr.user_id = ? AND gr.is_active = 1 
                  AND gr.proximo_pago <= DATE_ADD(CURDATE(), INTERVAL 20 DAY)
                ORDER BY gr.proximo_pago ASC
            ");
            $stmt->execute([$userId]);
            $stats['proximos_pagos'] = $stmt->fetchAll();

            // Próximos pagos de tarjetas
            $stmt = $db->prepare("SELECT * FROM tarjetas_credito WHERE user_id = ? AND is_active = 1 ORDER BY dia_pago ASC");
            $stmt->execute([$userId]);
            $stats['tarjetas'] = $stmt->fetchAll();

            // Metas de ahorro con progreso
            $stmt = $db->prepare("
                SELECT *, ROUND((monto_actual / monto_objetivo) * 100, 1) as porcentaje
                FROM metas_ahorro WHERE user_id = ? AND is_active = 1
                ORDER BY porcentaje DESC LIMIT 5
            ");
            $stmt->execute([$userId]);
            $stats['metas'] = $stmt->fetchAll();

        } catch (Exception $e) {
            error_log('Error getting dashboard stats: ' . $e->getMessage());
            $stats = [
                'ingresos_mes' => 0, 'gastos_mes' => 0, 'balance_mes' => 0,
                'total_ahorrado' => 0, 'total_tarjetas' => 0, 'total_recurrentes' => 0,
                'monto_recurrentes' => 0, 'alertas_no_leidas' => 0, 'total_metas' => 0,
                'gastos_por_categoria' => [], 'tendencia_mensual' => [],
                'ultimas_transacciones' => [], 'proximos_pagos' => [],
                'tarjetas' => [], 'metas' => []
            ];
        }

        return $stats;
    }
}
