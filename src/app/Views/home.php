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
        /* Base */
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .monitor-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .home-card {
            border-radius: 1.5rem;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .home-card:hover {
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .card-body {
            padding: 1.5rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        #data {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .metric-box {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            border-left: 4px solid;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .metric-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .metric-box.voltage { border-left-color: #ffc107; }
        .metric-box.current { border-left-color: #dc3545; }
        .metric-box.power { border-left-color: #fd7e14; }
        .metric-box.energy { border-left-color: #198754; }
        .metric-box.cost { border-left-color: #0dcaf0; }
        
        .metric-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .metric-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #343a40;
            margin-bottom: 0;
            word-break: break-word;
        }

        .metric-value small {
            font-size: 0.6rem;
            font-weight: 400;
        }

        .actions-divider {
            border-top: 2px solid #e9ecef;
            margin: 1.5rem 0;
        }

        .home-actions,
        .home-links {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn {
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            flex: 1;
            min-width: 140px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-outline-primary {
            border: 2px solid #3b82f6;
            color: #3b82f6;
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: #3b82f6;
            color: white;
        }

        .btn-outline-secondary {
            border: 2px solid #6b7280;
            color: #6b7280;
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: #6b7280;
            color: white;
        }

        /* Móvil - Muy pequeño */
        @media (max-width: 375px) {
            .monitor-container {
                padding: 0.75rem;
            }

            .card-body {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            #data {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
                gap: 0.75rem;
            }

            .metric-box {
                padding: 0.75rem;
            }

            .metric-label {
                font-size: 0.65rem;
            }

            .metric-value {
                font-size: 1.1rem;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
                min-width: 110px;
                flex: 1;
            }

            .actions-divider {
                margin: 1rem 0;
            }
        }

        /* Móvil - Pequeño */
        @media (max-width: 576px) {
            .monitor-container {
                padding: 1rem;
            }

            .home-actions,
            .home-links {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin: 0;
            }
        }

        /* Tablet */
        @media (min-width: 576px) {
            #data {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            }

            .home-actions,
            .home-links {
                justify-content: center;
            }
        }

        /* Desktop */
        @media (min-width: 768px) {
            .card-body {
                padding: 2rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .monitor-container {
                padding: 2rem;
            }

            #data {
                grid-template-columns: repeat(5, 1fr);
                gap: 1.5rem;
            }

            .metric-box {
                padding: 1.25rem;
            }

            .metric-label {
                font-size: 0.8rem;
            }

            .metric-value {
                font-size: 1.5rem;
            }

            .home-actions,
            .home-links {
                justify-content: space-between;
            }

            .btn {
                width: auto;
            }
        }

        /* Loading spinner */
        .spinner-border {
            width: 1.5rem;
            height: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="monitor-container">
        <div class="home-card" style="width: 100%; max-width: 1000px;">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-4">
                    <i class="bi bi-lightning-charge-fill text-warning me-2" style="font-size: 2rem;"></i>
                    <h1 class="page-title mb-0"><i class="bi bi-speedometer2"></i> Monitor de Energía</h1>
                </div>
                
                <div id="data" class="mb-3">
                    <!-- Los datos se injectan aquí -->
                    <div style="grid-column: 1 / -1; text-align: center; color: #6c757d;">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                        Cargando datos del sensor...
                    </div>
                </div>
                
                <div class="actions-divider"></div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex home-actions align-items-center justify-content-center justify-content-md-start h-100">
                            <button id="relayOn" class="btn btn-success flex-grow-1 flex-md-grow-0"><i class="bi bi-power me-1"></i> Encender</button>
                            <button id="relayOff" class="btn btn-danger flex-grow-1 flex-md-grow-0"><i class="bi bi-plugin me-1"></i> Apagar</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex home-links align-items-center justify-content-center justify-content-md-end h-100">
                            <a href="tariffs" class="btn btn-outline-primary flex-grow-1 flex-md-grow-0"><i class="bi bi-currency-dollar me-1"></i> Tarifas</a>
                            <a href="reports" class="btn btn-outline-secondary flex-grow-1 flex-md-grow-0"><i class="bi bi-graph-up me-1"></i> Reportes</a>
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
                        <div class="metric-box voltage">
                            <div class="metric-label"><i class="bi bi-speedometer me-1"></i>Voltaje</div>
                            <div class="metric-value">${data.voltage} <small>V</small></div>
                        </div>
                        <div class="metric-box current">
                            <div class="metric-label"><i class="bi bi-signpost-split me-1"></i>Corriente</div>
                            <div class="metric-value">${data.current} <small>A</small></div>
                        </div>
                        <div class="metric-box power">
                            <div class="metric-label"><i class="bi bi-lightning me-1"></i>Potencia</div>
                            <div class="metric-value">${data.power} <small>W</small></div>
                        </div>
                        <div class="metric-box energy">
                            <div class="metric-label"><i class="bi bi-battery-charging me-1"></i>Energía</div>
                            <div class="metric-value">${data.energy} <small>kWh</small></div>
                        </div>
                        <div class="metric-box cost">
                            <div class="metric-label"><i class="bi bi-cash-coin me-1"></i>Costo</div>
                            <div class="metric-value text-success">$${data.cost ? data.cost.toFixed(2) : '0.00'}</div>
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('data').innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #dc3545;"><i class="bi bi-exclamation-triangle"></i> Error al cargar datos.</div>';
                });
        }

        document.getElementById('relayOn').addEventListener('click', () => {
            fetch('api/relay', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ action: 'on' }) 
            });
        });

        document.getElementById('relayOff').addEventListener('click', () => {
            fetch('api/relay', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ action: 'off' }) 
            });
        });

        setInterval(updateData, 5000); // Actualizar cada 5 segundos
        updateData(); // Cargar inicialmente
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
