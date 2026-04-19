<!-- src/app/Views/home.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alcancia Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f8f9fa 0%, #eef3ff 100%);
        }

        .home-card {
            border-radius: 1rem;
            border: none;
        }

        .home-title {
            line-height: 1.2;
        }
        
        @media (max-width: 767.98px) {
            .home-card { margin-top: 1rem; }
            .metric-box { margin-bottom: 1rem; }
            .home-actions, .home-links { flex-direction: column; width: 100%; gap: 0.5rem !important; }
            .home-actions .btn, .home-links .btn { width: 100%; margin: 0 !important; }
            .home-links { margin-top: .25rem; }
        }

        @media (max-width: 575.98px) {
            .home-title {
                font-size: 1.35rem !important;
            }
            .metric-box {
                padding: .875rem;
            }
            .metric-value {
                font-size: 1.2rem;
            }
            .metric-label {
                font-size: .78rem;
            }
            .home-card .card-body {
                padding: 1.15rem !important;
            }
        }
        
        .metric-box {
            background: #f8f9fa;
            border-radius: 0.75rem;
            padding: 1rem;
            border-left: 4px solid #0d6efd;
            height: 100%;
        }
        .metric-box.voltage { border-left-color: #ffc107; }
        .metric-box.current { border-left-color: #dc3545; }
        .metric-box.power { border-left-color: #fd7e14; }
        .metric-box.energy { border-left-color: #198754; }
        .metric-box.cost { border-left-color: #0dcaf0; }
        
        .metric-label { font-size: 0.875rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
        .metric-value { font-size: 1.5rem; font-weight: 700; color: #343a40; margin-bottom: 0; }
    </style>
</head>
<body>
    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="card shadow-sm home-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex align-items-center justify-content-center mb-4">
                            <i class="bi bi-lightning-charge-fill text-warning fs-2 me-2"></i>
                            <h1 class="card-title text-center mb-0 fs-3 fs-md-2 fw-bold home-title">Monitor de Energía</h1>
                        </div>
                        
                        <div id="data" class="row g-3 mb-4">
                            <!-- Los datos se injectan aquí -->
                            <div class="col-12 text-center text-muted">
                                <output class="spinner-border spinner-border-sm text-primary me-2" aria-label="Cargando"></output>
                                Cargando datos del sensor...
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex gap-2 home-actions h-100 align-items-center justify-content-center justify-content-md-start">
                                    <button id="relayOn" class="btn btn-success flex-grow-1"><i class="bi bi-power me-1"></i> Encender Relay</button>
                                    <button id="relayOff" class="btn btn-danger flex-grow-1"><i class="bi bi-plugin me-1"></i> Apagar Relay</button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2 home-links h-100 align-items-center justify-content-center justify-content-md-end">
                                    <a href="tariffs" class="btn btn-outline-primary"><i class="bi bi-currency-dollar me-1"></i> Tarifas</a>
                                    <a href="reports" class="btn btn-outline-secondary"><i class="bi bi-graph-up me-1"></i> Reportes</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateData() {
            fetch('api/data')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('data').innerHTML = `
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="metric-box voltage">
                                <div class="metric-label"><i class="bi bi-speedometer me-1"></i>Voltaje</div>
                                <div class="metric-value">${data.voltage} <small class="text-muted fs-6">V</small></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="metric-box current">
                                <div class="metric-label"><i class="bi bi-signpost-split me-1"></i>Corriente</div>
                                <div class="metric-value">${data.current} <small class="text-muted fs-6">A</small></div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="metric-box power">
                                <div class="metric-label"><i class="bi bi-lightning me-1"></i>Potencia</div>
                                <div class="metric-value">${data.power} <small class="text-muted fs-6">W</small></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-6">
                            <div class="metric-box energy h-100">
                                <div class="metric-label"><i class="bi bi-battery-charging me-1"></i>Energía</div>
                                <div class="metric-value">${data.energy} <small class="text-muted fs-6">kWh</small></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="metric-box cost h-100">
                                <div class="metric-label"><i class="bi bi-cash-coin me-1"></i>Costo Estimado</div>
                                <div class="metric-value text-success">$${data.cost ? data.cost.toFixed(2) : '0.00'}</div>
                            </div>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('data').innerHTML = '<div class="col-12 text-center text-danger"><i class="bi bi-exclamation-triangle"></i> Error al cargar datos.</div>';
                });
        }

        document.getElementById('relayOn').addEventListener('click', () => {
            fetch('api/relay', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'on' }) });
        });

        document.getElementById('relayOff').addEventListener('click', () => {
            fetch('api/relay', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'off' }) });
        });

        setInterval(updateData, 5000); // Actualizar cada 5 segundos
        updateData(); // Cargar inicialmente
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
