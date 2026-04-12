<?php
// src/app/Views/dashboard.php
$pageTitle = 'Dashboard';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$rate = $stats['rate'] ?? 0;
$todayEnergy = floatval($stats['today']['today_energy'] ?? 0);
$todayCost = floatval($stats['today']['cost'] ?? 0);
$monthEnergy = floatval($stats['month']['month_energy'] ?? 0);
$monthCost = floatval($stats['month']['cost'] ?? 0);
$diffPercent = floatval($stats['today']['diff_percent'] ?? 0);

// Datos actuales
$currentVoltage = floatval($realtime['voltage'] ?? 0);
$currentCurrent = floatval($realtime['current_val'] ?? 0);
$currentPower   = floatval($realtime['power'] ?? 0);
$currentEnergy  = floatval($realtime['energy'] ?? 0);
$currentFreq    = floatval($realtime['frequency'] ?? 0);
$currentPF      = floatval($realtime['power_factor'] ?? 0);
$relayStatus    = $realtime['relay_status'] ?? 'OFF';
?>

<!-- Device Status Banner -->
<div class="device-status-banner <?php echo ($device['online'] ?? false) ? 'online' : 'offline'; ?>" id="deviceBanner">
    <div class="banner-left">
        <span class="status-pulse"></span>
        <span class="status-text">
            <?php if ($device['online'] ?? false): ?>
                <strong><?php echo htmlspecialchars($device['device_name'] ?? 'PZEM-004T'); ?></strong> — Conectado
            <?php else: ?>
                <strong>Dispositivo Desconectado</strong> — <?php echo $device['last_seen'] ? 'Última vez: ' . time_ago($device['last_seen']) : 'Nunca conectado'; ?>
            <?php endif; ?>
        </span>
    </div>
    <div class="banner-right">
        <span class="relay-badge <?php echo $relayStatus; ?>" id="relayBadge">
            <i class="fas fa-power-off"></i> Relay: <?php echo $relayStatus; ?>
        </span>
    </div>
</div>

<!-- Stats Cards Row -->
<div class="stats-grid">
    <!-- Voltaje -->
    <div class="stat-card" id="cardVoltage">
        <div class="stat-card-header">
            <div class="stat-icon voltage">
                <i class="fas fa-bolt"></i>
            </div>
            <span class="stat-trend neutral">
                <i class="fas fa-equals"></i>
            </span>
        </div>
        <div class="stat-value-group">
            <span class="stat-main-value" id="valueVoltage"><?php echo number_format($currentVoltage, 1); ?></span>
            <span class="stat-unit">V</span>
        </div>
        <span class="stat-label">Voltaje</span>
    </div>

    <!-- Corriente -->
    <div class="stat-card" id="cardCurrent">
        <div class="stat-card-header">
            <div class="stat-icon current">
                <i class="fas fa-water"></i>
            </div>
        </div>
        <div class="stat-value-group">
            <span class="stat-main-value" id="valueCurrent"><?php echo number_format($currentCurrent, 3); ?></span>
            <span class="stat-unit">A</span>
        </div>
        <span class="stat-label">Corriente</span>
    </div>

    <!-- Potencia -->
    <div class="stat-card highlight" id="cardPower">
        <div class="stat-card-header">
            <div class="stat-icon power">
                <i class="fas fa-fire"></i>
            </div>
        </div>
        <div class="stat-value-group">
            <span class="stat-main-value" id="valuePower"><?php echo number_format($currentPower, 1); ?></span>
            <span class="stat-unit">W</span>
        </div>
        <span class="stat-label">Potencia Activa</span>
    </div>

    <!-- Energía Acumulada -->
    <div class="stat-card" id="cardEnergy">
        <div class="stat-card-header">
            <div class="stat-icon energy">
                <i class="fas fa-battery-three-quarters"></i>
            </div>
        </div>
        <div class="stat-value-group">
            <span class="stat-main-value" id="valueEnergy"><?php echo number_format($currentEnergy, 3); ?></span>
            <span class="stat-unit">kWh</span>
        </div>
        <span class="stat-label">Energía Acumulada</span>
    </div>

    <!-- Frecuencia -->
    <div class="stat-card" id="cardFrequency">
        <div class="stat-card-header">
            <div class="stat-icon frequency">
                <i class="fas fa-wave-square"></i>
            </div>
        </div>
        <div class="stat-value-group">
            <span class="stat-main-value" id="valueFrequency"><?php echo number_format($currentFreq, 1); ?></span>
            <span class="stat-unit">Hz</span>
        </div>
        <span class="stat-label">Frecuencia</span>
    </div>

    <!-- Factor de Potencia -->
    <div class="stat-card" id="cardPF">
        <div class="stat-card-header">
            <div class="stat-icon pf">
                <i class="fas fa-percentage"></i>
            </div>
        </div>
        <div class="stat-value-group">
            <span class="stat-main-value" id="valuePF"><?php echo number_format($currentPF, 2); ?></span>
            <span class="stat-unit">PF</span>
        </div>
        <span class="stat-label">Factor de Potencia</span>
    </div>
</div>

<!-- Cost Summary -->
<div class="cost-summary-grid">
    <div class="cost-card today">
        <div class="cost-card-content">
            <div class="cost-info">
                <span class="cost-label">Consumo Hoy</span>
                <div class="cost-values">
                    <span class="cost-energy" id="todayEnergy"><?php echo number_format($todayEnergy, 3); ?> kWh</span>
                    <span class="cost-money" id="todayCost"><?php echo format_cop($todayCost); ?></span>
                </div>
            </div>
            <div class="cost-icon-wrap today">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <?php if ($diffPercent != 0): ?>
            <div class="cost-diff <?php echo $diffPercent > 0 ? 'up' : 'down'; ?>">
                <i class="fas fa-arrow-<?php echo $diffPercent > 0 ? 'up' : 'down'; ?>"></i>
                <span><?php echo abs($diffPercent); ?>% vs ayer</span>
            </div>
        <?php endif; ?>
    </div>

    <div class="cost-card month">
        <div class="cost-card-content">
            <div class="cost-info">
                <span class="cost-label">Consumo Mes</span>
                <div class="cost-values">
                    <span class="cost-energy" id="monthEnergy"><?php echo number_format($monthEnergy, 3); ?> kWh</span>
                    <span class="cost-money" id="monthCost"><?php echo format_cop($monthCost); ?></span>
                </div>
            </div>
            <div class="cost-icon-wrap month">
                <i class="fas fa-calendar-alt"></i>
            </div>
        </div>
    </div>

    <div class="cost-card rate">
        <div class="cost-card-content">
            <div class="cost-info">
                <span class="cost-label">Tarifa Activa</span>
                <div class="cost-values">
                    <span class="cost-money" id="rateValue"><?php echo format_cop($rate); ?></span>
                    <span class="cost-energy">por kWh</span>
                </div>
            </div>
            <div class="cost-icon-wrap rate">
                <i class="fas fa-tag"></i>
            </div>
        </div>
        <a href="<?php echo url('settings'); ?>" class="cost-action">Cambiar tarifa <i class="fas fa-arrow-right"></i></a>
    </div>
</div>

<!-- Charts Section -->
<div class="charts-grid">
    <!-- Gráfica Principal: Potencia en Tiempo Real -->
    <div class="chart-card main-chart">
        <div class="chart-card-header">
            <div class="chart-title-group">
                <h3>Potencia en Tiempo Real</h3>
                <span class="chart-subtitle">Actualización cada 5 segundos</span>
            </div>
            <div class="chart-controls">
                <button class="chart-period-btn active" data-period="realtime" id="btnRealtime">
                    <i class="fas fa-broadcast-tower"></i> En Vivo
                </button>
                <button class="chart-period-btn" data-period="24h" id="btn24h">24h</button>
                <button class="chart-period-btn" data-period="7d" id="btn7d">7d</button>
                <button class="chart-period-btn" data-period="30d" id="btn30d">30d</button>
            </div>
        </div>
        <div class="chart-wrapper">
            <canvas id="powerChart"></canvas>
        </div>
    </div>

    <!-- Gráfica de Voltaje y Corriente -->
    <div class="chart-card secondary-chart">
        <div class="chart-card-header">
            <div class="chart-title-group">
                <h3>Voltaje y Corriente</h3>
                <span class="chart-subtitle">Tendencia temporal</span>
            </div>
        </div>
        <div class="chart-wrapper">
            <canvas id="voltageCurrentChart"></canvas>
        </div>
    </div>

    <!-- Gráfica de Consumo Diario (Barras) -->
    <div class="chart-card secondary-chart">
        <div class="chart-card-header">
            <div class="chart-title-group">
                <h3>Consumo Diario</h3>
                <span class="chart-subtitle">kWh por día</span>
            </div>
        </div>
        <div class="chart-wrapper">
            <canvas id="dailyConsumptionChart"></canvas>
        </div>
    </div>
</div>

<!-- Inject data for JS -->
<script>
    window.energyData = {
        baseUrl: '<?php echo BASE_URL; ?>',
        realtime: <?php echo json_encode($realtimeReadings); ?>,
        chartData: <?php echo json_encode($chartData); ?>,
        rate: <?php echo $rate; ?>,
        deviceOnline: <?php echo ($device['online'] ?? false) ? 'true' : 'false'; ?>
    };
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
