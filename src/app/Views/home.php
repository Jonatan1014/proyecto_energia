<!-- src/app/Views/home.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alcancia Inteligente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media (max-width: 767.98px) {
            .home-card { border-radius: 1rem; }
            .home-actions { flex-direction: column; }
            .home-actions .btn,
            .home-links .btn { width: 100%; }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow home-card">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4 fs-3 fs-md-2">Estado de la Alcancia en Tiempo Real</h1>
                        <div id="data" class="text-center">
                            <p class="lead">Cargando datos...</p>
                        </div>
                        <div class="d-flex justify-content-center gap-2 mt-4 home-actions">
                            <button id="relayOn" class="btn btn-success">Encender Relay</button>
                            <button id="relayOff" class="btn btn-danger">Apagar Relay</button>
                        </div>
                        <div class="text-center mt-3 d-flex gap-2 justify-content-center home-links flex-column flex-md-row">
                            <a href="tariffs" class="btn btn-outline-primary me-2">Configurar Tarifas</a>
                            <a href="reports" class="btn btn-outline-secondary">Ver Reportes</a>
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
                        <p class="mb-2"><strong>Voltaje:</strong> ${data.voltage} V</p>
                        <p class="mb-2"><strong>Corriente:</strong> ${data.current} A</p>
                        <p class="mb-2"><strong>Potencia:</strong> ${data.power} W</p>
                        <p class="mb-2"><strong>Energía:</strong> ${data.energy} kWh</p>
                        <p class="mb-0"><strong>Costo:</strong> $${data.cost ? data.cost.toFixed(2) : 'N/A'}</p>
                    `;
                })
                .catch(error => console.error('Error:', error));
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
