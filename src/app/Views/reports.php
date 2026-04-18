<?php
// src/app/Views/reports.php
$pageTitle = 'Reportes de Consumo';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// Formatear fechas para los inputs
$startVal = htmlspecialchars($startDate);
$endVal = htmlspecialchars($endDate);
?>

<div class="reports-container">
    <!-- Header de Filtros -->
    <div class="section-header-premium">
        <div class="header-texts">
            <h1>Análisis de Consumo Histórico</h1>
            <p>Monitorea y analiza el comportamiento de tu red eléctrica</p>
        </div>
        
        <form method="GET" action="<?php echo url('reports'); ?>" class="reports-filter-card">
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt"></i> Fecha Inicio</label>
                <input type="date" name="start" value="<?php echo $startVal; ?>" required>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar-check"></i> Fecha Fin</label>
                <input type="date" name="end" value="<?php echo $endVal; ?>" required>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fas fa-sync-alt"></i> Aplicar Filtros
            </button>
        </form>
    </div>

    <!-- Resumen de Picos (KPIs) -->
    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); margin-bottom: 2rem;">
        <!-- Pico Máximo -->
        <div class="stat-card peak-high">
            <div class="stat-card-header">
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="stat-trend negative">Hora de Mayor Consumo</span>
            </div>
            <div class="stat-value-group">
                <span class="stat-main-value"><?php echo $maxHour['hora'] ?? '0'; ?>:00</span>
                <span class="stat-unit">Promedio <?php echo number_format($maxHour['avg_power'] ?? 0, 1); ?> W</span>
            </div>
            <span class="stat-label">Pico de Demanda Histórico</span>
        </div>

        <!-- Pico Mínimo -->
        <div class="stat-card peak-low">
            <div class="stat-card-header">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                    <i class="fas fa-leaf"></i>
                </div>
                <span class="stat-trend positive">Hora de Menor Consumo</span>
            </div>
            <div class="stat-value-group">
                <span class="stat-main-value"><?php echo $minHour['hora'] ?? '0'; ?>:00</span>
                <span class="stat-unit">Promedio <?php echo number_format($minHour['avg_power'] ?? 0, 1); ?> W</span>
            </div>
            <span class="stat-label">Consumo Base Mínimo</span>
        </div>

        <!-- Total en Rango -->
        <?php
            $totalKWh = 0;
            $totalCost = 0;
            foreach ($historical as $day) {
                $totalKWh += $day['daily_energy'];
                $totalCost += $day['cost'];
            }
        ?>
        <div class="stat-card total-range">
            <div class="stat-card-header">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <span class="stat-trend">Periodo Seleccionado</span>
            </div>
            <div class="stat-value-group">
                <span class="stat-main-value"><?php echo number_format($totalKWh, 2); ?></span>
                <span class="stat-unit">kWh Total</span>
            </div>
            <span class="stat-label">Costo: <?php echo format_cop($totalCost); ?></span>
        </div>
    </div>

    <!-- Gráficas Principales -->
    <div class="charts-grid-reports">
        <!-- Historial Diario (Rango) -->
        <div class="chart-container-premium">
            <div class="chart-header">
                <h3>Consumo Diario (kWh)</h3>
                <div class="chart-actions">
                    <span class="badge-period"><?php echo $startVal; ?> a <?php echo $endVal; ?></span>
                </div>
            </div>
            <div class="chart-wrapper">
                <canvas id="rangeChart"></canvas>
            </div>
        </div>

        <!-- Análisis de Picos (Perfil 24h) -->
        <div class="chart-container-premium">
            <div class="chart-header">
                <h3>Perfil de Carga (24h)</h3>
                <p>Promedio de potencia (W) por cada hora del día</p>
            </div>
            <div class="chart-wrapper">
                <canvas id="peakChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabla de Detalles -->
    <div class="data-table-card">
        <div class="card-header-table">
            <h3>Detalle de Lecturas Diarias</h3>
            <button class="btn-export" onclick="exportTableToCSV()"><i class="fas fa-file-csv"></i> Exportar</button>
        </div>
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Consumo (kWh)</th>
                        <th>Pot. Prom (W)</th>
                        <th>Pot. Máx (W)</th>
                        <th>Volt. Prom (V)</th>
                        <th>Costo Est.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historical)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No hay datos para este rango</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_reverse($historical) as $row): ?>
                            <tr>
                                <td class="font-bold"><?php echo date('d M, Y', strtotime($row['fecha'])); ?></td>
                                <td><?php echo number_format($row['daily_energy'], 3); ?> kWh</td>
                                <td><?php echo number_format($row['avg_power'], 1); ?> W</td>
                                <td><?php echo number_format($row['max_power'], 1); ?> W</td>
                                <td><?php echo number_format($row['avg_voltage'], 1); ?> V</td>
                                <td class="status-success"><?php echo format_cop($row['cost']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para Reportes (Mezcla de energy.css y mejoras) */
.reports-container {
    padding-bottom: 2rem;
}

.section-header-premium {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 2rem;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.header-texts h1 {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--text-heading);
    margin-bottom: 0.25rem;
}

.header-texts p {
    color: var(--text-secondary);
}

.reports-filter-card {
    background: var(--bg-card);
    padding: 1rem 1.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    display: flex;
    gap: 1.5rem;
    align-items: center;
    box-shadow: var(--shadow-md);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.filter-group label {
    font-size: 0.75rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group input {
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    padding: 0.5rem 0.8rem;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
    outline: none;
    transition: var(--transition);
}

.filter-group input:focus {
    border-color: var(--accent-primary);
}

.btn-filter {
    background: linear-gradient(135deg, var(--accent-primary), #2563eb);
    color: white;
    border: none;
    padding: 0.8rem 1.2rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px var(--accent-primary-glow);
}

.charts-grid-reports {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 1100px) {
    .charts-grid-reports {
        grid-template-columns: 1fr;
    }
}

.chart-container-premium {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
}

.chart-header {
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
}

.chart-header h3 {
    font-size: 1.1rem;
    color: var(--text-heading);
}

.badge-period {
    background: rgba(59, 130, 246, 0.1);
    color: var(--accent-primary);
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.chart-wrapper {
    height: 300px;
    width: 100%;
}

.data-table-card {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.card-header-table {
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}

.btn-export {
    background: var(--bg-surface);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.premium-table {
    width: 100%;
    border-collapse: collapse;
}

.premium-table th {
    background: var(--bg-secondary);
    text-align: left;
    padding: 1rem 1.5rem;
    font-size: 0.75rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.premium-table td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    font-size: 0.9rem;
}

.premium-table tr:hover {
    background: rgba(255,255,255,0.02);
}

.font-bold { font-weight: 600; }
.status-success { color: var(--accent-green); font-weight: 600; }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Gráfica de Rango Diario
    const historicalData = <?php echo json_encode($historical); ?>;
    const ctxRange = document.getElementById('rangeChart').getContext('2d');
    
    new Chart(ctxRange, {
        type: 'line',
        data: {
            labels: historicalData.map(d => d.fecha),
            datasets: [{
                label: 'Consumo (kWh)',
                data: historicalData.map(d => d.daily_energy),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6'
            }, {
                label: 'Potencia Máx (W)',
                data: historicalData.map(d => d.max_power),
                borderColor: '#ef4444',
                borderDash: [5, 5],
                pointRadius: 0,
                fill: false,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    title: { display: true, text: 'kWh' }
                },
                y1: {
                    position: 'right',
                    grid: { display: false },
                    title: { display: true, text: 'Watts' }
                },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { position: 'top', labels: { color: '#8b95a5' } }
            }
        }
    });

    // 2. Gráfica de Picos (24h)
    const peakData = <?php echo json_encode($peakHours); ?>;
    const ctxPeak = document.getElementById('peakChart').getContext('2d');
    
    // Preparar labels 00-23
    const hoursLabels = Array.from({length: 24}, (_, i) => `${i}:00`);
    const hoursValues = new Array(24).fill(0);
    peakData.forEach(p => {
        hoursValues[p.hora] = p.avg_power;
    });

    new Chart(ctxPeak, {
        type: 'bar',
        data: {
            labels: hoursLabels,
            datasets: [{
                label: 'Potencia Promedio (W)',
                data: hoursValues,
                backgroundColor: hoursValues.map(v => 
                    v === Math.max(...hoursValues) ? '#ef4444' : (v === Math.min(...hoursValues.filter(x => x > 0)) ? '#10b981' : '#3b82f6')
                ),
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});

function exportTableToCSV() {
    let csv = [];
    const rows = document.querySelectorAll("table tr");
    for (const row of rows) {
        let cols = row.querySelectorAll("td, th");
        let rowData = [];
        for (const col of cols) rowData.push('"' + col.innerText + '"');
        csv.push(rowData.join(","));
    }
    const csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
    const downloadLink = document.createElement("a");
    downloadLink.download = "reporte_energia_<?php echo $startVal; ?>.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>