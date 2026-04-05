<!-- src/app/Views/tariff_form.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Tarifa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media (max-width: 575.98px) {
            .tariff-form-card { border-radius: 1rem; }
            .tariff-form-title { font-size: 1.4rem; }
            .tariff-form-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            .tariff-form-actions .btn { width: 100%; }
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow tariff-form-card">
                    <div class="card-body">
                        <h1 class="card-title text-center mb-4 tariff-form-title"><?php echo isset($tariff) ? 'Editar' : 'Crear'; ?> Tarifa</h1>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="rate" class="form-label">Tarifa por kWh</label>
                                <input type="number" id="rate" step="0.01" name="rate" class="form-control" value="<?php echo $tariff['rate_per_kwh'] ?? ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="start_date" class="form-label">Fecha Inicio</label>
                                <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?php echo $tariff['start_date'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="end_date" class="form-label">Fecha Fin</label>
                                <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?php echo $tariff['end_date'] ?? ''; ?>">
                            </div>
                            <div class="d-flex justify-content-between tariff-form-actions">
                                <button type="submit" class="btn btn-primary">Guardar</button>
                                <a href="tariffs" class="btn btn-secondary">Volver</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
