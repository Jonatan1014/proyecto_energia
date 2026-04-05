<?php include_once __DIR__ . '/includes/header.php'; ?>
<?php include_once __DIR__ . '/includes/sidebar.php'; ?>

<?php
$alcancia = $estado['alcancia'] ?? [];
$metas = $estado['metas'] ?? [];
$depositos = $estado['ultimos_depositos'] ?? [];
$retiros = $estado['ultimos_retiros'] ?? [];
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
        <button id="btn-sync-oled" type="button" class="btn btn-sm btn-outline-primary ms-2">Sincronizar OLED</button>
        <button id="btn-vaciar-alcancia" type="button" class="btn btn-sm btn-outline-danger ms-2">Vaciar alcancia</button>
    </div>
</div>

<div id="panel-mensajes" class="alert d-none" role="alert"></div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-start border-4 border-success">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Total Ahorrado</div>
                <div id="total-ahorrado" class="fs-3 fw-bold text-success"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($totalAhorrado, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-start border-4 border-primary">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Meta General</div>
                <div id="meta-general" class="fs-3 fw-bold text-primary"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format($metaGeneral, 0, ',', '.'); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm border-start border-4 border-info">
            <div class="card-body">
                <div class="text-muted small text-uppercase">Depositos Registrados</div>
                <div id="total-depositos" class="fs-3 fw-bold text-info"><?php echo number_format((int)$resumen['total_depositos'], 0, ',', '.'); ?></div>
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
            <progress id="barra-avance" class="w-100" max="100" value="<?php echo min(100, max(0, $avanceGeneral)); ?>" style="height:12px;"></progress>
        </div>
        <div class="d-flex justify-content-between mt-2 text-muted small">
            <span id="texto-avance"><?php echo number_format($avanceGeneral, 2, ',', '.'); ?>% completado</span>
            <span id="texto-acumulado">Acumulado en depositos: <?php echo CURRENCY_SYMBOL; ?> <?php echo number_format((float)$resumen['acumulado_depositos'], 0, ',', '.'); ?></span>
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
            <div class="card-body" id="metas-container">
                <?php if (empty($metas)): ?>
                    <p class="text-muted mb-0">No hay metas registradas.</p>
                <?php else: ?>
                    <?php foreach ($metas as $meta): ?>
                        <?php
                            $montoActual = (float)($meta['monto_actual'] ?? 0);
                            $montoObjetivo = (float)($meta['monto_objetivo'] ?? 0);
                            $avance = $montoObjetivo > 0 ? min(100, ($montoActual / $montoObjetivo) * 100) : 0;
                        ?>
                        <div class="mb-3 border rounded p-3 bg-light-subtle" data-meta-id="<?php echo (int)$meta['id']; ?>">
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
                            <form class="form-editar-meta mt-3" data-meta-id="<?php echo (int)$meta['id']; ?>">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small mb-1" for="meta-nombre-<?php echo (int)$meta['id']; ?>">Nombre meta</label>
                                        <input id="meta-nombre-<?php echo (int)$meta['id']; ?>" type="text" class="form-control form-control-sm" name="nombre" maxlength="120" value="<?php echo htmlspecialchars($meta['nombre']); ?>" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small mb-1" for="meta-monto-<?php echo (int)$meta['id']; ?>">Monto objetivo</label>
                                        <input id="meta-monto-<?php echo (int)$meta['id']; ?>" type="number" class="form-control form-control-sm" name="monto_objetivo" min="1" step="1" value="<?php echo (float)$montoObjetivo; ?>" required>
                                    </div>
                                    <div class="col-md-2 d-grid">
                                        <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                                    </div>
                                </div>
                            </form>
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
                            <tbody id="tabla-depositos-body">
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

<div class="card mt-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Historial de Retiros</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Fecha</th>
                        <th>Monto retirado</th>
                        <th>Usuario</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody id="tabla-retiros-body">
                    <?php if (empty($retiros)): ?>
                        <tr id="empty-retiros-row">
                            <td class="ps-3 text-muted" colspan="4">Aun no hay retiros registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($retiros as $r): ?>
                            <tr>
                                <td class="ps-3"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                                <td class="fw-semibold text-danger"><?php echo CURRENCY_SYMBOL; ?> <?php echo number_format((float)$r['monto_retirado'], 0, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($r['usuario_nombre'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($r['motivo'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
<script>
    const WS_CONFIG = {
        key: '<?php echo addslashes(SOKETI_APP_KEY); ?>',
        wsHost: '<?php echo addslashes(SOKETI_WS_HOST); ?>',
        wsPort: <?php echo (int)SOKETI_WS_PORT; ?>,
        forceTLS: <?php echo SOKETI_FORCE_TLS ? 'true' : 'false'; ?>,
        authEndpoint: 'api/ws/auth'
    };

    function formatMoney(value) {
        return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(Number(value || 0));
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(Number(value || 0));
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderEstado(payload) {
        const data = payload?.data || payload;
        const alcancia = data?.alcancia || {};
        const resumen = data?.resumen || {};
        const depositos = Array.isArray(data?.ultimos_depositos) ? data.ultimos_depositos : [];
        const metas = Array.isArray(data?.metas) ? data.metas : [];
        const retiros = Array.isArray(data?.ultimos_retiros) ? data.ultimos_retiros : [];

        const total = Number(alcancia.total_ahorrado || 0);
        const meta = Number(alcancia.meta_general || 0);
        const avance = Number(alcancia.avance_general_porcentaje || 0);
        const acumulado = Number(resumen.acumulado_depositos || 0);
        const totalDepositos = Number(resumen.total_depositos || 0);

        const totalAhorradoEl = document.getElementById('total-ahorrado');
        const metaGeneralEl = document.getElementById('meta-general');
        const totalDepositosEl = document.getElementById('total-depositos');
        const barraAvanceEl = document.getElementById('barra-avance');
        const textoAvanceEl = document.getElementById('texto-avance');
        const textoAcumuladoEl = document.getElementById('texto-acumulado');
        const tablaBodyEl = document.getElementById('tabla-depositos-body');
        const metasContainerEl = document.getElementById('metas-container');
        const tablaRetirosBodyEl = document.getElementById('tabla-retiros-body');

        if (totalAhorradoEl) totalAhorradoEl.textContent = '$ ' + formatMoney(total);
        if (metaGeneralEl) metaGeneralEl.textContent = '$ ' + formatMoney(meta);
        if (totalDepositosEl) totalDepositosEl.textContent = formatNumber(totalDepositos);
        if (barraAvanceEl) barraAvanceEl.value = Math.max(0, Math.min(100, avance));
        if (textoAvanceEl) textoAvanceEl.textContent = avance.toFixed(2).replace('.', ',') + '% completado';
        if (textoAcumuladoEl) textoAcumuladoEl.textContent = 'Acumulado en depositos: $ ' + formatMoney(acumulado);

        if (metasContainerEl) {
            metasContainerEl.innerHTML = '';
            if (metas.length === 0) {
                metasContainerEl.innerHTML = '<p class="text-muted mb-0">No hay metas registradas.</p>';
            } else {
                metas.forEach((meta) => {
                    const montoActual = Number(meta.monto_actual || 0);
                    const montoObjetivo = Number(meta.monto_objetivo || 0);
                    const avanceMeta = montoObjetivo > 0 ? Math.min(100, (montoActual / montoObjetivo) * 100) : 0;
                    const metaId = Number(meta.id || 0);
                    const metaHtml =
                        '<div class="mb-3 border rounded p-3 bg-light-subtle" data-meta-id="' + metaId + '">' +
                            '<div class="d-flex justify-content-between">' +
                                '<strong>' + escapeHtml(meta.nombre || '') + '</strong>' +
                                '<span class="text-muted small">' + avanceMeta.toFixed(1).replace('.', ',') + '%</span>' +
                            '</div>' +
                            '<div class="small text-muted mb-1">$ ' + formatMoney(montoActual) + ' de $ ' + formatMoney(montoObjetivo) + '</div>' +
                            '<div class="progress" style="height: 8px;">' +
                                '<div class="progress-bar ' + (meta.activa ? 'bg-primary' : 'bg-secondary') + '" style="width: ' + avanceMeta + '%"></div>' +
                            '</div>' +
                            '<form class="form-editar-meta mt-3" data-meta-id="' + metaId + '">' +
                                '<div class="row g-2 align-items-end">' +
                                    '<div class="col-md-5">' +
                                        '<label class="form-label small mb-1" for="meta-nombre-js-' + metaId + '">Nombre meta</label>' +
                                        '<input id="meta-nombre-js-' + metaId + '" type="text" class="form-control form-control-sm" name="nombre" maxlength="120" required value="' + escapeHtml(meta.nombre || '') + '">' +
                                    '</div>' +
                                    '<div class="col-md-5">' +
                                        '<label class="form-label small mb-1" for="meta-monto-js-' + metaId + '">Monto objetivo</label>' +
                                        '<input id="meta-monto-js-' + metaId + '" type="number" class="form-control form-control-sm" name="monto_objetivo" min="1" step="1" required value="' + montoObjetivo + '">' +
                                    '</div>' +
                                    '<div class="col-md-2 d-grid">' +
                                        '<button type="submit" class="btn btn-sm btn-primary">Guardar</button>' +
                                    '</div>' +
                                '</div>' +
                            '</form>' +
                        '</div>';
                    metasContainerEl.insertAdjacentHTML('beforeend', metaHtml);
                });
                bindMetaForms();
            }
        }

        if (tablaBodyEl) {
            tablaBodyEl.innerHTML = '';
            if (depositos.length === 0) {
                tablaBodyEl.innerHTML = '<tr><td class="ps-3 text-muted" colspan="4">No hay depositos registrados.</td></tr>';
            } else {
                depositos.forEach((d) => {
                    const row = document.createElement('tr');
                    row.innerHTML =
                        '<td class="ps-3">' + (d.created_at || '') + '</td>' +
                        '<td class="fw-semibold text-success">$ ' + formatMoney(d.monto || 0) + '</td>' +
                        '<td>' + (d.pulsos ?? '-') + '</td>' +
                        '<td>' + (d.origen || '') + '</td>';
                    tablaBodyEl.appendChild(row);
                });
            }
        }

        if (tablaRetirosBodyEl) {
            tablaRetirosBodyEl.innerHTML = '';
            if (retiros.length === 0) {
                tablaRetirosBodyEl.innerHTML = '<tr id="empty-retiros-row"><td class="ps-3 text-muted" colspan="4">Aun no hay retiros registrados.</td></tr>';
            } else {
                retiros.forEach((r) => {
                    const row = document.createElement('tr');
                    row.innerHTML =
                        '<td class="ps-3">' + escapeHtml(r.created_at || '') + '</td>' +
                        '<td class="fw-semibold text-danger">$ ' + formatMoney(r.monto_retirado || 0) + '</td>' +
                        '<td>' + escapeHtml(r.usuario_nombre || '') + '</td>' +
                        '<td>' + escapeHtml(r.motivo || '-') + '</td>';
                    tablaRetirosBodyEl.appendChild(row);
                });
            }
        }
    }

    function showMessage(type, text) {
        const panel = document.getElementById('panel-mensajes');
        if (!panel) return;

        panel.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
        panel.classList.add('alert-' + (type || 'info'));
        panel.textContent = text || '';
    }

    function bindMetaForms() {
        const forms = document.querySelectorAll('.form-editar-meta');
        forms.forEach((form) => {
            if (form.dataset.bound === '1') {
                return;
            }

            form.dataset.bound = '1';
            form.addEventListener('submit', (event) => {
                event.preventDefault();

                const metaId = Number(form.getAttribute('data-meta-id') || 0);
                const formData = new FormData(form);
                const payload = {
                    meta_id: metaId,
                    nombre: String(formData.get('nombre') || ''),
                    monto_objetivo: Number(formData.get('monto_objetivo') || 0)
                };

                fetch('api/alcancia/meta/actualizar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                })
                    .then((r) => r.json())
                    .then((data) => {
                        if (!data.ok) {
                            showMessage('danger', data.error || 'No se pudo actualizar la meta');
                            return;
                        }

                        showMessage('success', data.message || 'Meta actualizada');
                        renderEstado(data);
                    })
                    .catch(() => showMessage('danger', 'Error de red actualizando la meta'));
            });
        });
    }

    function bindVaciarButton() {
        const btn = document.getElementById('btn-vaciar-alcancia');
        if (!btn) {
            return;
        }

        btn.addEventListener('click', () => {
            const motivo = window.prompt('Motivo del retiro (opcional):', '') || '';
            const confirmar = window.confirm('Se vaciara la alcancia y quedara en cero. Continuar?');
            if (!confirmar) {
                return;
            }

            fetch('api/alcancia/vaciar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ motivo })
            })
                .then((r) => r.json())
                .then((data) => {
                    if (!data.ok) {
                        showMessage('danger', data.error || 'No se pudo vaciar la alcancia');
                        return;
                    }

                    showMessage('success', data.message || 'Alcancia vaciada correctamente');
                    renderEstado(data);
                })
                .catch(() => showMessage('danger', 'Error de red vaciando alcancia'));
        });
    }

    function startRealtime() {
        if (typeof window.Pusher !== 'undefined') {
            window.Pusher.logToConsole = false;

            const pusher = new window.Pusher(WS_CONFIG.key, {
                wsHost: WS_CONFIG.wsHost,
                wsPort: WS_CONFIG.wsPort,
                forceTLS: WS_CONFIG.forceTLS,
                enabledTransports: ['ws', 'wss'],
                authEndpoint: WS_CONFIG.authEndpoint,
                auth: {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            });

            const channel = pusher.subscribe('private-alcancia.1');
            channel.bind('deposito.registrado', (eventData) => {
                if (eventData && eventData.estado) {
                    renderEstado(eventData.estado);
                }
            });

            channel.bind('comando.emitido', (eventData) => {
                console.log('Comando emitido:', eventData);
            });

            channel.bind('meta.actualizada', (eventData) => {
                if (eventData && eventData.estado) {
                    renderEstado(eventData.estado);
                }
            });

            channel.bind('alcancia.vaciada', (eventData) => {
                if (eventData && eventData.estado) {
                    renderEstado(eventData.estado);
                }
            });

            channel.bind('pusher:subscription_error', () => {
                startSSEOrPollingFallback();
            });

            const syncBtn = document.getElementById('btn-sync-oled');
            if (syncBtn) {
                syncBtn.addEventListener('click', () => {
                    fetch('api/alcancia/comando', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            accion: 'sync_state',
                            datos: { requested_from: 'dashboard' }
                        })
                    })
                        .then((r) => r.json())
                        .then((data) => {
                            if (!data.ok) {
                                console.error('No se pudo enviar comando:', data);
                            }
                        })
                        .catch((e) => console.error('Error enviando comando:', e));
                });
            }

            return;
        }

        startSSEOrPollingFallback();
    }

    function startSSEOrPollingFallback() {
        if (typeof EventSource !== 'undefined') {
            const source = new EventSource('api/alcancia/stream');
            source.addEventListener('estado', (event) => {
                try {
                    const payload = JSON.parse(event.data);
                    renderEstado(payload);
                } catch (e) {
                    console.error('Error parseando stream:', e);
                }
            });

            source.onerror = () => {
                source.close();
                startPollingFallback();
            };
        } else {
            startPollingFallback();
        }
    }

    function startPollingFallback() {
        const refresh = () => {
            fetch('api/alcancia/status?limit=10')
                .then((r) => r.json())
                .then((payload) => renderEstado(payload))
                .catch((e) => console.error('Error refrescando estado:', e));
        };

        refresh();
        setInterval(refresh, 5000);
    }

    bindMetaForms();
    bindVaciarButton();
    startRealtime();
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
