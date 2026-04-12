/**
 * EnergyMonitor - Main Application JavaScript
 * Handles: sidebar, real-time data polling, Chart.js graphs, device status
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initDeviceStatus();

    // Only init charts and polling on dashboard
    if (document.getElementById('powerChart')) {
        initCharts();
        startPolling();
    }
});

/* =====================================================
   SIDEBAR
   ===================================================== */
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('menuToggle');
    const close = document.getElementById('sidebarClose');

    if (toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.add('open');
            overlay.classList.add('active');
        });
    }

    const closeSidebar = () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
    };

    if (close) close.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
}

/* =====================================================
   DEVICE STATUS
   ===================================================== */
function initDeviceStatus() {
    updateDeviceIndicator();
    setInterval(updateDeviceIndicator, 15000); // Check every 15s
}

function updateDeviceIndicator() {
    const indicator = document.getElementById('deviceIndicator');
    if (!indicator) return;

    const dot = indicator.querySelector('.device-dot');
    const label = indicator.querySelector('.device-label');

    // If we have energyData from dashboard page
    if (window.energyData) {
        if (window.energyData.deviceOnline) {
            dot.className = 'device-dot online';
            label.textContent = 'Conectado';
        } else {
            dot.className = 'device-dot offline';
            label.textContent = 'Desconectado';
        }
    }
}

/* =====================================================
   REAL-TIME DATA POLLING
   ===================================================== */
let pollingInterval = null;

function startPolling() {
    // Poll every 5 seconds
    pollingInterval = setInterval(fetchRealtimeData, 5000);
}

function fetchRealtimeData() {
    const baseUrl = window.energyData?.baseUrl || '';
    
    fetch(`${baseUrl}/api/data`)
        .then(res => res.json())
        .then(result => {
            if (result.status === 'success' && result.data) {
                updateDashboardValues(result.data);
            }
        })
        .catch(err => console.log('Polling error:', err));

    // Also update device status
    fetch(`${baseUrl}/api/device-status`)
        .then(res => res.json())
        .then(result => {
            if (result.status === 'success') {
                const d = result.data;
                const banner = document.getElementById('deviceBanner');
                const dot = document.getElementById('deviceIndicator')?.querySelector('.device-dot');
                const label = document.getElementById('deviceIndicator')?.querySelector('.device-label');

                if (banner) {
                    banner.className = `device-status-banner ${d.online ? 'online' : 'offline'}`;
                }
                if (dot) dot.className = `device-dot ${d.online ? 'online' : 'offline'}`;
                if (label) label.textContent = d.online ? 'Conectado' : 'Desconectado';

                window.energyData.deviceOnline = d.online;
            }
        })
        .catch(() => {});
}

function updateDashboardValues(data) {
    const updates = {
        'valueVoltage': data.voltage ? parseFloat(data.voltage).toFixed(1) : '—',
        'valueCurrent': data.current_val ? parseFloat(data.current_val).toFixed(3) : '—',
        'valuePower': data.power ? parseFloat(data.power).toFixed(1) : '—',
        'valueEnergy': data.energy ? parseFloat(data.energy).toFixed(3) : '—',
        'valueFrequency': data.frequency ? parseFloat(data.frequency).toFixed(1) : '—',
        'valuePF': data.power_factor ? parseFloat(data.power_factor).toFixed(2) : '—',
    };

    for (const [id, value] of Object.entries(updates)) {
        const el = document.getElementById(id);
        if (el && el.textContent !== value) {
            el.textContent = value;
            el.classList.add('updated');
            setTimeout(() => el.classList.remove('updated'), 400);
        }
    }

    // Update relay badge
    const relayBadge = document.getElementById('relayBadge');
    if (relayBadge && data.relay_status) {
        relayBadge.className = `relay-badge ${data.relay_status}`;
        relayBadge.innerHTML = `<i class="fas fa-power-off"></i> Relay: ${data.relay_status}`;
    }

    // Add data point to real-time chart
    if (powerChart && data.power) {
        const now = new Date();
        const label = now.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        
        addChartPoint(powerChart, label, parseFloat(data.power));
        
        if (voltageCurrentChart && data.voltage && data.current_val) {
            addDualChartPoint(
                voltageCurrentChart, 
                label, 
                parseFloat(data.voltage), 
                parseFloat(data.current_val)
            );
        }
    }
}

/* =====================================================
   CHARTS
   ===================================================== */
let powerChart = null;
let voltageCurrentChart = null;
let dailyConsumptionChart = null;

const chartColors = {
    primary: '#3b82f6',
    primaryGlow: 'rgba(59, 130, 246, 0.2)',
    green: '#10b981',
    greenGlow: 'rgba(16, 185, 129, 0.2)',
    amber: '#f59e0b',
    amberGlow: 'rgba(245, 158, 11, 0.2)',
    cyan: '#06b6d4',
    cyanGlow: 'rgba(6, 182, 212, 0.2)',
    purple: '#8b5cf6',
    purpleGlow: 'rgba(139, 92, 246, 0.2)',
    gridColor: 'rgba(255, 255, 255, 0.05)',
    tickColor: 'rgba(255, 255, 255, 0.4)',
};

const defaultChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index',
    },
    plugins: {
        legend: {
            display: true,
            position: 'top',
            labels: {
                color: chartColors.tickColor,
                font: { family: "'Inter', sans-serif", size: 11 },
                usePointStyle: true,
                pointStyleWidth: 8,
                padding: 15,
            }
        },
        tooltip: {
            backgroundColor: '#141b2d',
            borderColor: 'rgba(59, 130, 246, 0.3)',
            borderWidth: 1,
            titleColor: '#e8eaed',
            bodyColor: '#8b95a5',
            titleFont: { family: "'Inter', sans-serif", weight: 600 },
            bodyFont: { family: "'JetBrains Mono', monospace", size: 12 },
            padding: 10,
            cornerRadius: 8,
            displayColors: true,
        }
    },
    scales: {
        x: {
            grid: { color: chartColors.gridColor, drawBorder: false },
            ticks: { color: chartColors.tickColor, font: { size: 10 } },
        },
        y: {
            grid: { color: chartColors.gridColor, drawBorder: false },
            ticks: { color: chartColors.tickColor, font: { size: 10 } },
        }
    }
};

function initCharts() {
    const data = window.energyData || {};
    const realtime = data.realtime || [];
    const chartData = data.chartData || [];

    initPowerChart(realtime);
    initVoltageCurrentChart(realtime);
    initDailyConsumptionChart(chartData);

    // Period buttons
    document.querySelectorAll('.chart-period-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.chart-period-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const period = btn.dataset.period;
            handlePeriodChange(period);
        });
    });
}

function initPowerChart(realtime) {
    const ctx = document.getElementById('powerChart');
    if (!ctx) return;

    const labels = realtime.map(r => {
        const d = new Date(r.timestamp);
        return d.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    });
    const values = realtime.map(r => parseFloat(r.power));

    powerChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Potencia (W)',
                data: values,
                borderColor: chartColors.primary,
                backgroundColor: createGradient(ctx, chartColors.primary, chartColors.primaryGlow),
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: chartColors.primary,
            }]
        },
        options: {
            ...defaultChartOptions,
            plugins: {
                ...defaultChartOptions.plugins,
                legend: { display: false }
            }
        }
    });
}

function initVoltageCurrentChart(realtime) {
    const ctx = document.getElementById('voltageCurrentChart');
    if (!ctx) return;

    const labels = realtime.map(r => {
        const d = new Date(r.timestamp);
        return d.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
    });

    voltageCurrentChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Voltaje (V)',
                    data: realtime.map(r => parseFloat(r.voltage)),
                    borderColor: chartColors.amber,
                    backgroundColor: chartColors.amberGlow,
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    pointRadius: 0,
                    yAxisID: 'y',
                },
                {
                    label: 'Corriente (A)',
                    data: realtime.map(r => parseFloat(r.current_val)),
                    borderColor: chartColors.cyan,
                    backgroundColor: chartColors.cyanGlow,
                    borderWidth: 2,
                    fill: false,
                    tension: 0.3,
                    pointRadius: 0,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            ...defaultChartOptions,
            scales: {
                ...defaultChartOptions.scales,
                y: {
                    ...defaultChartOptions.scales.y,
                    position: 'left',
                    title: { display: true, text: 'Voltaje (V)', color: chartColors.tickColor, font: { size: 10 } }
                },
                y1: {
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: chartColors.tickColor, font: { size: 10 } },
                    title: { display: true, text: 'Corriente (A)', color: chartColors.tickColor, font: { size: 10 } }
                }
            }
        }
    });
}

function initDailyConsumptionChart(chartData) {
    const ctx = document.getElementById('dailyConsumptionChart');
    if (!ctx) return;

    const labels = chartData.map(d => {
        // Could be hour or day format
        if (d.hora) {
            const date = new Date(d.hora);
            return date.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
        }
        if (d.fecha) {
            const date = new Date(d.fecha);
            return date.toLocaleDateString('es-CO', { day: '2-digit', month: 'short' });
        }
        return '';
    });

    const energyValues = chartData.map(d => parseFloat(d.max_energy || d.daily_energy || d.avg_power || 0));
    const rate = window.energyData?.rate || 0;
    const costValues = energyValues.map(e => (e * rate));

    dailyConsumptionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Energía (kWh)',
                    data: energyValues,
                    backgroundColor: chartColors.primaryGlow,
                    borderColor: chartColors.primary,
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Costo (COP)',
                    data: costValues,
                    type: 'line',
                    borderColor: chartColors.green,
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 3,
                    pointBackgroundColor: chartColors.green,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            ...defaultChartOptions,
            scales: {
                ...defaultChartOptions.scales,
                y: {
                    ...defaultChartOptions.scales.y,
                    title: { display: true, text: 'kWh', color: chartColors.tickColor, font: { size: 10 } }
                },
                y1: {
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: chartColors.tickColor, font: { size: 10 } },
                    title: { display: true, text: 'COP $', color: chartColors.tickColor, font: { size: 10 } }
                }
            }
        }
    });
}

/* Helper: add point to real-time chart */
function addChartPoint(chart, label, value, maxPoints = 40) {
    chart.data.labels.push(label);
    chart.data.datasets[0].data.push(value);

    if (chart.data.labels.length > maxPoints) {
        chart.data.labels.shift();
        chart.data.datasets[0].data.shift();
    }
    chart.update('none'); // No animation for smoother updates
}

function addDualChartPoint(chart, label, value1, value2, maxPoints = 40) {
    chart.data.labels.push(label);
    chart.data.datasets[0].data.push(value1);
    chart.data.datasets[1].data.push(value2);

    if (chart.data.labels.length > maxPoints) {
        chart.data.labels.shift();
        chart.data.datasets[0].data.shift();
        chart.data.datasets[1].data.shift();
    }
    chart.update('none');
}

/* Handle period change for main chart */
function handlePeriodChange(period) {
    const baseUrl = window.energyData?.baseUrl || '';

    if (period === 'realtime') {
        // Switch back to realtime mode - will be fed by polling
        fetch(`${baseUrl}/api/realtime?count=20`)
            .then(res => res.json())
            .then(result => {
                if (result.status === 'success' && powerChart) {
                    const readings = result.data || [];
                    powerChart.data.labels = readings.map(r => {
                        const d = new Date(r.timestamp);
                        return d.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    });
                    powerChart.data.datasets[0].data = readings.map(r => parseFloat(r.power));
                    powerChart.update();
                }
            })
            .catch(err => console.error('Error fetching realtime:', err));
        return;
    }

    fetch(`${baseUrl}/api/chart-data?period=${period}`)
        .then(res => res.json())
        .then(result => {
            if (result.status === 'success' && powerChart) {
                const data = result.data || [];
                const labels = data.map(d => {
                    if (d.hora) {
                        const date = new Date(d.hora);
                        return date.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
                    }
                    if (d.fecha) {
                        const date = new Date(d.fecha);
                        return date.toLocaleDateString('es-CO', { day: '2-digit', month: 'short' });
                    }
                    return '';
                });
                const values = data.map(d => parseFloat(d.avg_power || 0));

                powerChart.data.labels = labels;
                powerChart.data.datasets[0].data = values;
                powerChart.data.datasets[0].label = `Potencia Promedio (W) - ${period}`;
                powerChart.update();
            }
        })
        .catch(err => console.error('Error fetching chart data:', err));
}

/* Create gradient for chart fills */
function createGradient(ctx, color, fadeColor) {
    const el = ctx.getContext ? ctx : ctx.canvas;
    const context = el.getContext('2d');
    const gradient = context.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, fadeColor);
    gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');
    return gradient;
}
