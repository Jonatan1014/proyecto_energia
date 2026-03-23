/**
 * FinanzApp Charts Logic (using Chart.js)
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Solo ejecutar si Chart.js está disponible
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado');
        return;
    }

    // Verificar si estamos en una página que tiene gráficos
    const hasCharts = document.getElementById('trendChart') || document.getElementById('categoryChart');
    if (!hasCharts) {
        console.log('No hay gráficos en esta página, omitiendo inicialización');
        return;
    }

    console.log('Inicializando gráficos...');

    // Colores base que se adaptan al tema
    const getChartColors = () => {
        const isDark = document.documentElement.classList.contains('dark-mode');
        return {
            textColor: isDark ? '#f8fafc' : '#0f172a',
            gridColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)',
            primary: '#4f46e5',
            success: '#10b981',
            danger: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6',
            palette: ['#4f46e5', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b']
        };
    };

    // Configuración global de Chart.js
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = getChartColors().textColor;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
    Chart.defaults.plugins.tooltip.titleFont.family = "'Outfit', sans-serif";
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.elements.line.tension = 0.4; // Curvas suaves

    // Referencias a los gráficos para poder actualizarlos si cambia el tema
    const activeCharts = [];

    // Función para inicializar gráficos
    const initCharts = () => {
        try {
            const colors = getChartColors();
            
            console.log('Datos de gráficos disponibles:', window.chartData);
            
            // Verificar que los datos estén disponibles
            if (!window.chartData) {
                console.warn('chartData no está disponible');
                return;
            }
        
        // 1. Gráfico de Tendencia Mensual (Líneas)
        const tendenciaCanvas = document.getElementById('trendChart');
        console.log('Canvas trendChart encontrado:', !!tendenciaCanvas);
        if (tendenciaCanvas && window.chartData.trend) {
            console.log('Inicializando gráfico de tendencia con datos:', window.chartData.trend);
            const data = window.chartData.trend;
            
            const labels = data.map(d => 'Día ' + d.dia);
            const ingresos = data.map(d => d.ingresos);
            const gastos = data.map(d => d.gastos);
            
            activeCharts.push(new Chart(tendenciaCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Ingresos',
                            data: ingresos,
                            borderColor: colors.success,
                            backgroundColor: colors.success + '20',
                            borderWidth: 2,
                            fill: true,
                            pointBackgroundColor: colors.success,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        },
                        {
                            label: 'Gastos',
                            data: gastos,
                            borderColor: colors.danger,
                            backgroundColor: colors.danger + '20',
                            borderWidth: 2,
                            fill: true,
                            pointBackgroundColor: colors.danger,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 6 } }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: colors.gridColor, drawBorder: false },
                            ticks: { 
                                callback: function(value) { return '$' + new Intl.NumberFormat('es-CO').format(value); }
                            }
                        },
                        x: {
                            grid: { display: false, drawBorder: false }
                        }
                    },
                    interaction: { mode: 'index', intersect: false }
                }
            }));
        }

        // 2. Gráfico Gastos por Categoría (Doughnut)
        const categoriasCanvas = document.getElementById('categoryChart');
        console.log('Canvas categoryChart encontrado:', !!categoriasCanvas);
        if (categoriasCanvas && window.chartData.categories) {
            console.log('Inicializando gráfico de categorías con datos:', window.chartData.categories);
            const data = window.chartData.categories;
            
            const labels = [];
            const amounts = [];
            const bgColors = [];
            
            data.forEach((item, index) => {
                labels.push(item.nombre);
                amounts.push(item.total);
                // Usar el color de la categoría si existe, o uno de la paleta
                bgColors.push(item.color || colors.palette[index % colors.palette.length]);
            });
            
            if(data.length === 0) {
                // Estado vacío
                labels.push('Sin gastos');
                amounts.push(1);
                bgColors.push(document.documentElement.classList.contains('dark-mode') ? '#334155' : '#e2e8f0');
            }
            
            activeCharts.push(new Chart(categoriasCanvas, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: amounts,
                        backgroundColor: bgColors,
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    if(data.length === 0) return ' No hay datos este mes';
                                    let label = context.label || '';
                                    if (label) { label += ': '; }
                                    if (context.parsed !== null) {
                                        label += '$' + new Intl.NumberFormat('es-CO').format(context.parsed);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            }));
        }
        } catch (error) {
            console.error('Error al inicializar gráficos:', error);
        }
    };

    // Función para verificar y inicializar gráficos
    let attempts = 0;
    const maxAttempts = 50; // Máximo 5 segundos (50 * 100ms)
    
    const tryInitCharts = () => {
        attempts++;
        
        if (window.chartData) {
            console.log('Datos encontrados, inicializando gráficos...');
            initCharts();
            console.log('Inicialización de gráficos completada');
        } else if (attempts < maxAttempts) {
            console.log(`Datos no disponibles aún, esperando... (intento ${attempts}/${maxAttempts})`);
            setTimeout(tryInitCharts, 100);
        } else {
            console.warn('Timeout: No se encontraron datos de gráficos después de', maxAttempts * 100, 'ms');
        }
    };
    
    // Iniciar verificación de datos
    tryInitCharts();
    });

