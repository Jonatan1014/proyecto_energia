<!-- src/app/Views/reports.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Alcancia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media (max-width: 767.98px) {
            .reports-card { border-radius: 1rem; }
            .reports-title { font-size: 1.35rem; }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow reports-card">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4 reports-title">Reportes Historicos de Depositos</h1>
                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="start" class="form-label">Fecha Inicio</label>
                                <input type="date" id="start" name="start" class="form-control" value="<?php echo $_GET['start'] ?? date('Y-m-d', strtotime('-7 days')); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="end" class="form-label">Fecha Fin</label>
                                <input type="date" id="end" name="end" class="form-control" value="<?php echo $_GET['end'] ?? date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Generar Reporte</button>
                            </div>
                        </form>
                        <div class="w-100" style="min-height:280px;">
                            <canvas id="energyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('energyChart').getContext('2d');
        const data = <?php echo json_encode($reports); ?>;
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => d.timestamp),
                datasets: [{
                    label: 'Potencia (W)',
                    data: data.map(d => d.power),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false
                }, {
                    label: 'Energía (kWh)',
                    data: data.map(d => d.energy),
                    borderColor: 'rgba(153, 102, 255, 1)',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
