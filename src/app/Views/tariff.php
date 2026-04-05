<!-- src/app/Views/tariff.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Tarifas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4">Configuración de Tarifas Energéticas</h1>
                        <div class="text-center mb-4">
                            <a href="tariffs/create" class="btn btn-primary">Agregar Nueva Tarifa</a>
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
                                            <a href="tariffs/update/<?php echo $tariff['id']; ?>" class="btn btn-sm btn-warning me-2">Editar</a>
                                            <a href="tariffs/delete/<?php echo $tariff['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Eliminar</a>
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