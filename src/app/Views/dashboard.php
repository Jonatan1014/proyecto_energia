<!-- src/app/Views/dashboard.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Medidor de Energía</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <h1>Dashboard de Energía</h1>
    <div id="data">
        <p>Voltaje: <?php echo $data['voltage'] ?? 'N/A'; ?> V</p>
        <p>Corriente: <?php echo $data['current'] ?? 'N/A'; ?> A</p>
        <p>Potencia: <?php echo $data['power'] ?? 'N/A'; ?> W</p>
        <p>Energía: <?php echo $data['energy'] ?? 'N/A'; ?> kWh</p>
        <p>Costo: $<?php echo $data['cost'] ? number_format($data['cost'], 2) : 'N/A'; ?></p>
    </div>
    <button id="relayOn">Encender Relay</button>
    <button id="relayOff">Apagar Relay</button>
    <a href="/tariffs">Configurar Tarifas</a>
    <a href="/reports">Ver Reportes</a>
    <a href="/perfil">Mi Perfil</a>
    <a href="/logout">Cerrar Sesión</a>
    <script>
        function updateData() {
            fetch('/api/data')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('data').innerHTML = `
                        <p>Voltaje: ${data.voltage} V</p>
                        <p>Corriente: ${data.current} A</p>
                        <p>Potencia: ${data.power} W</p>
                        <p>Energía: ${data.energy} kWh</p>
                        <p>Costo: $${data.cost ? data.cost.toFixed(2) : 'N/A'}</p>
                    `;
                })
                .catch(error => console.error('Error:', error));
        }

        document.getElementById('relayOn').addEventListener('click', () => {
            fetch('/api/relay', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'on' }) });
        });

        document.getElementById('relayOff').addEventListener('click', () => {
            fetch('/api/relay', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'off' }) });
        });

        setInterval(updateData, 5000);
    </script>
</body>
</html>
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Ingresos Mensuales</h6>
                    <h3 class="mb-0 fw-bold text-success"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($stats['ingresos_mes'], 0, ',', '.'); ?></h3>
                </div>
                <div class="fs-1 text-success opacity-25">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gastos -->
    <div class="col">
        <div class="card h-100 border-start border-4 border-danger shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Gastos Mensuales</h6>
                    <h3 class="mb-0 fw-bold text-danger"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($stats['gastos_mes'], 0, ',', '.'); ?></h3>
                </div>
                <div class="fs-1 text-danger opacity-25">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ahorros Totales -->
    <div class="col">
        <div class="card h-100 border-start border-4 border-primary shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted text-uppercase mb-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">Ahorro en Metas</h6>
                    <h3 class="mb-0 fw-bold text-primary"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($stats['total_ahorrado'] ?? 0, 0, ',', '.'); ?></h3>
                </div>
                <div class="fs-1 text-primary opacity-25">
                    <i class="fas fa-piggy-bank"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =======================
     ASESOR INTELIGENTE (Presupuesto)
     ======================= -->
<?php if(isset($presupuesto) && $presupuesto['ingreso_esperado'] > 0): ?>

    <!-- ALERTAS CRÍTICAS -->
    <?php if (!empty($presupuesto['alertas_advisor'])): ?>
        <?php foreach($presupuesto['alertas_advisor'] as $alerta): ?>
            <div class="alert alert-<?php echo ($alerta['tipo'] == 'grave' ? 'danger' : 'warning'); ?> d-flex align-items-center mb-4 shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2 fs-4"></i>
                <div><?php echo $alerta['mensaje']; ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="card mb-4 border-primary shadow-sm">
        <div class="card-header bg-primary bg-opacity-10 border-bottom-0">
             <div class="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 p-2" style="width: 48px; height: 48px;">
                    <i class="fas fa-brain fs-4"></i>
                </div>
                <div>
                    <h5 class="card-title fw-bold text-white m-0">Asesor Inteligente: Flujo de Caja</h5>
                    <p class="text-black small m-0">Dinero Real vs Compromisos Pendientes (Hasta el día <?php echo date('d', strtotime($presupuesto['proximo_pago'])); ?>).</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row text-center mb-4">
                <!-- Columna 1: Saldo Total -->
                <div class="col-lg-3 col-6 mb-3 mb-lg-0 border-end">
                    <div class="text-uppercase text-muted small fw-bold mb-1">Tu Dinero Total</div>
                    <div class="fs-4 text-dark font-monospace fw-bold">
                        <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($presupuesto['total_saldo_actual'], 0, ',', '.'); ?>
                    </div>
                </div>
                
                <!-- Columna 2: Fijos Pendientes -->
                <div class="col-lg-3 col-6 mb-3 mb-lg-0 border-end">
                    <div class="text-uppercase text-muted small fw-bold mb-1">Deudas Pendientes</div>
                    <div class="fs-4 text-danger font-monospace">
                        - <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($presupuesto['fijos_pendientes'], 0, ',', '.'); ?>
                    </div>
                    <small class="text-muted" style="font-size: 0.75rem;">A vencer antes de tu pago</small>
                </div>

                <!-- Columna 3: Ahorro Pendiente -->
                <div class="col-lg-3 col-6 mb-3 mb-lg-0 border-end">
                    <div class="text-uppercase text-muted small fw-bold mb-1">Ahorro Pendiente</div>
                     <div class="fs-4 text-warning font-monospace">
                        - <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($presupuesto['ahorro_pendiente'], 0, ',', '.'); ?>
                    </div>
                     <small class="text-muted" style="font-size: 0.75rem;"><?php echo $user['porcentaje_ahorro']; ?>% de ingresos sin transferir</small>
                </div>
                
                <!-- Columna 4: REALMENTE DISPONIBLE -->
                <div class="col-lg-3 col-12">
                    <div class="text-uppercase text-primary small fw-bold mb-1">Libre para Gastar</div>
                    <div class="fs-2 fw-bolder <?php echo $presupuesto['dinero_disponible_real'] >= 0 ? 'text-success' : 'text-danger'; ?> font-monospace">
                        <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($presupuesto['dinero_disponible_real'], 0, ',', '.'); ?>
                    </div>
                    <div class="badge bg-light text-dark mt-1 border">
                        Max <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($presupuesto['limite_diario'], 0, ',', '.'); ?> / día
                    </div>
                </div>
            </div>

            <div class="progress" style="height: 10px;" role="progressbar" aria-label="Porcentaje comprometido" aria-valuenow="<?php echo min(100, isset($porcentajeComprometido) ? $porcentajeComprometido : 0); ?>" aria-valuemin="0" aria-valuemax="100">
                <?php 
                    $totalDinero = $presupuesto['total_saldo_actual'];
                    $totalComprometido = $presupuesto['fijos_pendientes'] + $presupuesto['ahorro_pendiente'];
                    $porcentajeComprometido = ($totalDinero > 0) ? ($totalComprometido / $totalDinero) * 100 : 100;
                    $colorBar = $porcentajeComprometido > 90 ? 'danger' : ($porcentajeComprometido > 50 ? 'warning' : 'success');
                ?>
                <div class="progress-bar bg-<?php echo $colorBar; ?>" style="width: <?php echo min(100, $porcentajeComprometido); ?>%"></div>
            </div>
            <div class="d-flex justify-content-between mt-2 text-muted small">
                <span><?php echo number_format($porcentajeComprometido, 1); ?>% de tu dinero está comprometido</span>
                <span><?php echo $presupuesto['dias_restantes']; ?> días restantes</span>
            </div>

        </div>
    </div>

<?php elseif(isset($presupuesto)): ?>
    <div class="card mb-4 bg-light border-0 shadow-sm">
        <div class="card-body text-center p-5">
            <i class="fas fa-brain fa-3x text-primary opacity-50 mb-3"></i>
            <h4>Activa tu Asesor Inteligente</h4>
            <p class="text-muted">Añade tu ingreso esperado y metas de ahorro en tu perfil para que el sistema analice tu presupuesto diario.</p>
            <a href="perfil" class="btn btn-outline-primary mt-2">Configurar ahora</a>
        </div>
    </div>
<?php endif; ?>

<!-- =======================
     GRÁFICOS: TRENDS & PIE
     ======================= -->
<div class="row mb-4">
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3 ps-3">
                <h5 class="card-title fw-bold">Tendencia de Ingresos vs Gastos</h5>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px; width:100%">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-white border-bottom-0 pt-3 ps-3">
                <h5 class="card-title fw-bold">Gastos por Categoría</h5>
            </div>
            <div class="card-body pb-0">
                <?php if(empty($stats['gastos_por_categoria'])): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie opacity-25 fs-1 mb-3 text-muted"></i>
                        <p class="text-muted fw-medium">No has registrado gastos en este mes.</p>
                        <a href="transaccion/crear?tipo=gasto" class="btn btn-sm btn-outline-primary mt-2">Crear primer gasto</a>
                    </div>
                <?php else: ?>
                    <div class="chart-container" style="position: relative; height:200px; width:100%">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    
                    <div class="mt-4">
                        <?php foreach($stats['gastos_por_categoria'] as $index => $cat): if($index > 3) break; ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="d-inline-block rounded-circle me-2" style="width: 10px; height: 10px; background-color: <?php echo $cat['color']; ?>"></span>
                                    <span class="small text-muted"><?php echo htmlspecialchars($cat['nombre']); ?></span>
                                </div>
                                <span class="small fw-bold"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($cat['total'], 0, ',', '.'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- =======================
     MIS CUENTAS Y SALDOS
     ======================= -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title fw-bold mb-0">Mis Cuentas y Saldos</h5>
                <a href="cuentas" class="btn btn-sm btn-outline-primary">Gestionar Cuentas</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php if(empty($cuentas)): ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted">No has registrado cuentas bancarias aún.</p>
                            <a href="cuentas" class="btn btn-primary">Registrar ahora</a>
                        </div>
                    <?php else: ?>
                        <?php foreach($cuentas as $index => $c): if($index > 3) break; ?>
                            <div class="col-lg-3 col-md-6">
                                <div class="card h-100 border-start border-3 shadow-sm bg-light" style="border-color: <?php echo $c['color']; ?> !important;">
                                    <div class="card-body d-flex align-items-center">
                                         <div class="rounded-circle d-flex align-items-center justify-content-center me-3 text-white shadow-sm flex-shrink-0" style="background: <?php echo $c['color']; ?>; width:48px; height:48px;">
                                            <i class="<?php echo $c['icono']; ?>"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="fw-bold text-truncate text-dark text-opacity-75 small"><?php echo htmlspecialchars($c['nombre']); ?></div>
                                            <div class="fs-5 fw-bold text-dark"><?php echo CURRENCY_SYMBOL . number_format($c['saldo_actual'], 0, ',', '.'); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if(count($cuentas) > 4): ?>
                            <div class="col-12 text-end">
                                <a href="cuentas" class="small text-primary fw-bold text-decoration-none">Ver <?php echo (count($cuentas)-4); ?> cuentas más <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =======================
     TABLAS E INFORMACIÓN SECUNDARIA
     ======================= -->
<div class="row mb-4">
    <!-- Últimas Transacciones -->
    <div class="col-lg-8 mb-4 mb-lg-0">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title fw-bold mb-0">Últimas Transacciones</h5>
                <a href="transacciones" class="btn btn-sm btn-link text-decoration-none">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if(empty($stats['ultimas_transacciones'])): ?>
                    <div class="text-center p-5">
                        <p class="text-muted mb-0">Aún no has registrado transacciones.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="border-0 ps-4">Categoría</th>
                                    <th scope="col" class="border-0">Detalle</th>
                                    <th scope="col" class="border-0">Fecha</th>
                                    <th scope="col" class="text-end border-0 pe-4">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($stats['ultimas_transacciones'] as $t): ?>
                                    <tr>
                                        <td class="ps-4" style="width: 50px;">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="background-color: <?php echo $t['categoria_color'] ?? '#6c757d'; ?>20; color: <?php echo $t['categoria_color'] ?? '#6c757d'; ?>; width: 40px; height: 40px;">
                                                <i class="<?php echo $t['categoria_icono'] ?? 'fas fa-tag'; ?>"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark"><?php echo htmlspecialchars($t['categoria_nombre'] ?? 'Sin categorizar'); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($t['descripcion']); ?></div>
                                            <?php if(isset($t['cuenta_nombre']) && $t['cuenta_nombre']): ?>
                                                <span class="badge bg-light text-secondary border fw-normal mt-1">
                                                    <i class="<?php echo $t['cuenta_icono'] ?? 'fas fa-wallet'; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($t['cuenta_nombre']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small"><?php echo date('d M', strtotime($t['fecha'])); ?></td>
                                        <td class="text-end pe-4">
                                            <span class="fw-bold <?php echo $t['tipo'] === 'ingreso' ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $t['tipo'] === 'ingreso' ? '+' : '-'; ?> 
                                                <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($t['monto'], 0, ',', '.'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Próximos Eventos (Pagos) -->
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="card-title fw-bold mb-0">Próximos Pagos</h5>
                <a href="recurrentes" class="btn btn-sm btn-link text-decoration-none">Ver todo</a>
            </div>
            <div class="card-body p-0 overflow-auto custom-scrollbar" style="max-height: 400px;">
                <?php 
                $proximosEventos = [];
                // Logic for events (kept from original)
                if (!empty($stats['proximos_pagos'])) {
                    foreach($stats['proximos_pagos'] as $p) {
                        $nombre = $p['nombre'];
                        if (!empty($p['cuotas_totales']) && $p['cuotas_totales'] > 0) {
                             $cuotaActual = ($p['cuotas_pagadas'] ?? 0) + 1;
                             $nombre .= " ($cuotaActual/{$p['cuotas_totales']})";
                        }
                        $proximosEventos[] = [
                            'tipo_evento' => 'recurrente',
                            'id' => $p['id'],
                            'nombre' => $nombre,
                            'fecha' => $p['proximo_pago'],
                            'monto' => $p['monto'],
                            'icono' => $p['categoria_icono'] ?? 'fas fa-sync',
                            'color' => $p['categoria_color'] ?? null
                        ];
                    }
                }
                $diaActual = intval(date('d'));
                if (!empty($stats['tarjetas'])) {
                    foreach($stats['tarjetas'] as $t) {
                        $diasParaPago = $t['dia_pago'] - $diaActual;
                        if ($diasParaPago < 0) $diasParaPago += intval(date('t'));
                        if ($diasParaPago <= 20 && $diasParaPago >= 0) {
                            $fechaVence = date('Y-m-d', strtotime('+' . $diasParaPago . ' days'));
                            $proximosEventos[] = [
                                'tipo_evento' => 'tarjeta',
                                'nombre' => 'Tarjeta ' . $t['nombre'],
                                'fecha' => $fechaVence,
                                'icono' => 'fas fa-credit-card',
                                'color' => $t['color'] ?? '#666'
                            ];
                        }
                    }
                }
                usort($proximosEventos, function($a, $b) {
                    return strtotime($a['fecha']) - strtotime($b['fecha']);
                });
                
                if(empty($proximosEventos)): ?>
                    <div class="text-center p-4">
                        <i class="fas fa-check-circle text-success fs-1 mb-3 opacity-50"></i>
                        <h6 class="fw-bold">¡Todo al día!</h6>
                        <p class="text-sm text-muted">No tienes pagos programados ni vencidos.</p>
                        <a href="recurrente/crear" class="btn btn-sm btn-outline-primary mt-2">Añadir recurrente</a>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($proximosEventos as $ev): 
                            $fechaObj = new DateTime($ev['fecha']);
                            $hoy = new DateTime();
                            $hoy->setTime(0,0,0);
                            $fechaObj->setTime(0,0,0); // Comparar solo fechas
                            
                            $interval = $hoy->diff($fechaObj);
                            $daysDiff = (int)$interval->format('%r%a');
                            
                            $badgeClass = 'bg-light text-dark border';
                            $statusText = '';
                            
                            if ($daysDiff < 0) {
                                $badgeClass = 'bg-danger text-white';
                                $statusText = 'Vencido (' . abs($daysDiff) . 'd)';
                            } elseif ($daysDiff == 0) {
                                $badgeClass = 'bg-danger text-white';
                                $statusText = 'HOY';
                            } elseif ($daysDiff == 1) {
                                $badgeClass = 'bg-warning text-dark';
                                $statusText = 'Mañana';
                            } elseif ($daysDiff <= 7) {
                                 $badgeClass = 'bg-warning text-dark bg-opacity-75';
                                 $statusText = 'En ' . $daysDiff . ' días';
                            } else {
                                $statusText = date('d M', strtotime($ev['fecha']));
                            }
                        ?>
                            <li class="list-group-item d-flex align-items-center justify-content-between px-3 py-3 border-bottom-0 border-top">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="background-color: <?php echo ($ev['color'] ?? '#6c757d'); ?>20; color: <?php echo ($ev['color'] ?? '#6c757d'); ?>; width: 40px; height: 40px;">
                                        <i class="<?php echo $ev['icono']; ?>"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h6 class="mb-0 fw-bold text-truncate text-dark" style="max-width: 120px; font-size: 0.9rem;"><?php echo htmlspecialchars($ev['nombre']); ?></h6>
                                        <span class="badge rounded-pill <?php echo $badgeClass; ?> fw-normal mt-1" style="font-size: 0.7rem;"><?php echo $statusText; ?></span>
                                    </div>
                                </div>
                                <div class="text-end ps-2">
                                    <?php if(isset($ev['monto'])): ?>
                                        <div class="fw-bold fs-6 <?php echo $daysDiff <= 0 ? 'text-danger' : 'text-dark'; ?>">
                                            <?php echo CURRENCY_SYMBOL . number_format($ev['monto'], 0, ',', '.'); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if($ev['tipo_evento'] == 'recurrente' && $daysDiff <= 5): ?>
                                        <?php if(isset($ev['id'])): ?>
                                            <a href="recurrente/pagar?id=<?php echo $ev['id']; ?>" class="btn btn-xs btn-outline-success mt-1 py-0 px-2 rounded-pill shadow-sm" style="font-size: 0.7rem;">Pagar</a>
                                        <?php else: ?>
                                            <a href="recurrentes" class="text-xs text-primary text-decoration-none">Ir <i class="fas fa-arrow-right"></i></a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Progreso de Metas Estelar -->
        <?php if(!empty($stats['metas'])): ?>
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white border-bottom-0 pt-3">
                <h6 class="card-title fw-bold mb-0">Meta Destacada</h6>
            </div>
            <div class="card-body pt-0 pb-3">
                <?php $meta = $stats['metas'][0]; ?>
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="background-color: <?php echo $meta['color']; ?>20; color: <?php echo $meta['color']; ?>; width: 40px; height: 40px;">
                        <i class="<?php echo $meta['icono']; ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-bold text-dark small"><?php echo htmlspecialchars($meta['nombre']); ?></span>
                            <span class="fw-bold text-primary small"><?php echo min(100, $meta['porcentaje']); ?>%</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar rounded-pill" role="progressbar" style="width: <?php echo min(100, $meta['porcentaje']); ?>%; background-color: <?php echo $meta['color']; ?>"></div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                    <span><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($meta['monto_actual'], 0, ',', '.'); ?></span>
                    <span><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($meta['monto_objetivo'], 0, ',', '.'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- =======================
     INYECCIÓN DE DATOS PARA CHARTS.JS
     ======================= -->
<script>
    window.chartData = {
        trend: <?php echo json_encode($stats['tendencia_mensual']); ?>,
        categories: <?php echo json_encode($stats['gastos_por_categoria']); ?>
    };
</script>
