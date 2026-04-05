<?php include_once __DIR__ . '/includes/header.php'; ?>
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>

<?php
$alcancia = $estado['alcancia'] ?? [];
$metas = $estado['metas'] ?? [];
$depositos = $estado['ultimos_depositos'] ?? [];
$resumen = $estado['resumen'] ?? ['total_depositos' => 0, 'acumulado_depositos' => 0];

$totalAhorrado = (float)($alcancia['total_ahorrado'] ?? 0);
$metaGeneral = (float)($alcancia['meta_general'] ?? 0);
$avanceGeneral = (float)($alcancia['avance_general_porcentaje'] ?? 0);
$moneda = $alcancia['moneda'] ?? 'COP';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-1 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 mb-1">Resumen de Alcancia</h1>
        <p class="text-muted mb-0">Monitorea tus depositos, metas y avance de ahorro.</p>
    </div>
    <div class="text-end">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
            Moneda: <?php echo htmlspecialchars($moneda); ?>
        </span>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Total Ahorrado</div>
                <div class="fs-3 fw-bold text-success"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($totalAhorrado, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Meta General</div>
                <div class="fs-3 fw-bold text-primary"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($metaGeneral, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Depositos Registrados</div>
                <div class="fs-3 fw-bold text-info"><?php echo number_format((int)$resumen['total_depositos'], 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Progreso General</h5>
    </div>
    <div class="card-body">
        <div class="progress" style="height: 12px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, max(0, $avanceGeneral)); ?>%;"></div>
        </div>
        <div class="d-flex justify-content-between mt-2 text-muted small">
            <span><?php echo number_format($avanceGeneral, 2, ',', '.'); ?>% completado</span>
            <span>Acumulado en depositos: <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format((float)$resumen['acumulado_depositos'], 0, ',', '.'); ?></span>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Metas de Ahorro</h5>
                <span class="badge bg-secondary"><?php echo count($metas); ?> metas</span>
            </div>
            <div class="card-body">
                <?php if (empty($metas)): ?>
                    <p class="text-muted mb-0">No hay metas registradas.</p>
                <?php else: ?>
                    <?php foreach ($metas as $meta): ?>
                        <?php
                            $montoActual = (float)($meta['monto_actual'] ?? 0);
                            $montoObjetivo = (float)($meta['monto_objetivo'] ?? 0);
                            $avance = $montoObjetivo > 0 ? min(100, ($montoActual / $montoObjetivo) * 100) : 0;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($meta['nombre']); ?></strong>
                                <span class="text-muted small"><?php echo number_format($avance, 1, ',', '.'); ?>%</span>
                            </div>
                            <div class="small text-muted mb-1">
                                <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($montoActual, 0, ',', '.'); ?> de <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($montoObjetivo, 0, ',', '.'); ?>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar <?php echo !empty($meta['activa']) ? 'bg-primary' : 'bg-secondary'; ?>" style="width: <?php echo $avance; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Ultimos Depositos</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($depositos)): ?>
                    <div class="p-3 text-muted">No hay depositos registrados.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Monto</th>
                                    <th>Pulsos</th>
                                    <th>Origen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($depositos as $d): ?>
                                    <tr>
                                        <td class="ps-3"><?php echo htmlspecialchars($d['created_at'] ?? ''); ?></td>
                                        <td class="fw-semibold text-success"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format((float)$d['monto'], 0, ',', '.'); ?></td>
                                        <td><?php echo isset($d['pulsos']) ? (int)$d['pulsos'] : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($d['origen'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
