<?php
// src/config/routes.php
// FinanzApp - Gestor de Finanzas Personales

return [
    // ========================================
    // AUTENTICACIÓN
    // ========================================
    '/' => ['controller' => 'AuthController', 'action' => 'handleLogin'],
    '/login' => ['controller' => 'AuthController', 'action' => 'handleLogin'],
    '/register' => ['controller' => 'AuthController', 'action' => 'handleRegister'],
    '/logout' => ['controller' => 'AuthController', 'action' => 'logout'],

    // ========================================
    // DASHBOARD
    // ========================================
    '/dashboard' => ['controller' => 'DashboardController', 'action' => 'index'],

    // ========================================
    // TRANSACCIONES (Ingresos y Gastos)
    // ========================================
    '/transacciones' => ['controller' => 'TransaccionController', 'action' => 'index'],
    '/transaccion/crear' => ['controller' => 'TransaccionController', 'action' => 'create'],
    '/transaccion/guardar' => ['controller' => 'TransaccionController', 'action' => 'store'],
    '/transaccion/editar' => ['controller' => 'TransaccionController', 'action' => 'edit'],
    '/transaccion/actualizar' => ['controller' => 'TransaccionController', 'action' => 'update'],
    '/transaccion/eliminar' => ['controller' => 'TransaccionController', 'action' => 'delete'],

    // ========================================
    // TARJETAS DE CRÉDITO
    // ========================================
    '/tarjetas' => ['controller' => 'TarjetaController', 'action' => 'index'],
    '/tarjeta/crear' => ['controller' => 'TarjetaController', 'action' => 'create'],
    '/tarjeta/guardar' => ['controller' => 'TarjetaController', 'action' => 'store'],
    '/tarjeta/editar' => ['controller' => 'TarjetaController', 'action' => 'edit'],
    '/tarjeta/actualizar' => ['controller' => 'TarjetaController', 'action' => 'update'],
    '/tarjeta/eliminar' => ['controller' => 'TarjetaController', 'action' => 'delete'],

    // ========================================
    // CUENTAS Y SALDOS
    // ========================================
    '/cuentas' => ['controller' => 'CuentaController', 'action' => 'index'],
    '/cuenta/guardar' => ['controller' => 'CuentaController', 'action' => 'store'],
    '/cuenta/actualizar' => ['controller' => 'CuentaController', 'action' => 'update'],

    // ========================================
    // GASTOS RECURRENTES
    // ========================================
    '/recurrentes' => ['controller' => 'RecurrenteController', 'action' => 'index'],
    '/recurrente/crear' => ['controller' => 'RecurrenteController', 'action' => 'create'],
    '/recurrente/guardar' => ['controller' => 'RecurrenteController', 'action' => 'store'],
    '/recurrente/editar' => ['controller' => 'RecurrenteController', 'action' => 'edit'],
    '/recurrente/actualizar' => ['controller' => 'RecurrenteController', 'action' => 'update'],
    '/recurrente/eliminar' => ['controller' => 'RecurrenteController', 'action' => 'delete'],
    '/recurrente/compartir' => ['controller' => 'RecurrenteController', 'action' => 'compartir'],
    '/recurrente/pagar' => ['controller' => 'RecurrenteController', 'action' => 'confirmarPago'],
    '/recurrente/procesar_pago' => ['controller' => 'RecurrenteController', 'action' => 'procesarPago'],

    // ========================================
    // METAS DE AHORRO
    // ========================================
    '/metas' => ['controller' => 'MetaController', 'action' => 'index'],
    '/meta/crear' => ['controller' => 'MetaController', 'action' => 'create'],
    '/meta/guardar' => ['controller' => 'MetaController', 'action' => 'store'],
    '/meta/editar' => ['controller' => 'MetaController', 'action' => 'edit'],
    '/meta/actualizar' => ['controller' => 'MetaController', 'action' => 'update'],
    '/meta/eliminar' => ['controller' => 'MetaController', 'action' => 'delete'],
    '/meta/aportar' => ['controller' => 'MetaController', 'action' => 'aportar'],

    // ========================================
    // ALERTAS Y RECORDATORIOS
    // ========================================
    '/alertas' => ['controller' => 'AlertaController', 'action' => 'index'],
    '/alerta/crear' => ['controller' => 'AlertaController', 'action' => 'create'],
    '/alerta/guardar' => ['controller' => 'AlertaController', 'action' => 'store'],
    '/alerta/leer' => ['controller' => 'AlertaController', 'action' => 'markRead'],
    '/alerta/leer-todas' => ['controller' => 'AlertaController', 'action' => 'markAllRead'],
    '/alerta/eliminar' => ['controller' => 'AlertaController', 'action' => 'delete'],

    // ========================================
    // GASTOS COMPARTIDOS
    // ========================================
    '/compartido' => ['controller' => 'CompartidoController', 'action' => 'view'],
    '/compartido/aceptar' => ['controller' => 'CompartidoController', 'action' => 'accept'],

    // ========================================
    // PERFIL
    // ========================================
    '/perfil' => ['controller' => 'PerfilController', 'action' => 'index'],
    '/perfil/actualizar' => ['controller' => 'PerfilController', 'action' => 'update'],
    '/perfil/cambiar-password' => ['controller' => 'PerfilController', 'action' => 'changePassword'],
];