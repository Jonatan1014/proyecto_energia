<!-- src/app/Views/tariff.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Tarifas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media (max-width: 767.98px) {
            .tariff-card { border-radius: 1rem; }
            .tariff-title { font-size: 1.35rem; }
            .tariff-actions {
                display: flex;
                flex-direction: column;
                gap: 0.4rem;
            }
            .tariff-actions .btn {
                width: 100%;
                margin-right: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow tariff-card">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4 tariff-title">Configuración de Tarifas Energéticas</h1>
                        <div class="text-center mb-4">
                            <a href="tariffs/create" class="btn btn-primary w-100 w-md-auto">Agregar Nueva Tarifa</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Tarifa por kWh</th>
                                        <th>Fecha Inicio</th>
                                        <th>Fecha Fin</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tariffs as $tariff): ?>
                                    <tr>
                                        <td><?php echo $tariff['id']; ?></td>
                                        <td><?php echo $tariff['rate_per_kwh']; ?></td>
                                        <td><?php echo $tariff['start_date'] ?? 'N/A'; ?></td>
                                        <td><?php echo $tariff['end_date'] ?? 'N/A'; ?></td>
                                        <td>
                                            <div class="tariff-actions">
                                                <a href="tariffs/update/<?php echo $tariff['id']; ?>" class="btn btn-sm btn-warning me-2">Editar</a>
                                                <a href="tariffs/delete/<?php echo $tariff['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
