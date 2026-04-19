<?php
// src/app/Views/settings.php
$pageTitle = 'Configuración';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="settings-page">
    <div class="page-header">
        <h1><i class="fas fa-cog"></i> Configuración</h1>
        <p>Gestiona tus tarifas de energía y la configuración de tu dispositivo</p>
    </div>

    <div class="settings-grid">
        <!-- ========================================
             SECCIÓN: TARIFA kWh en COP 
             ======================================== -->
        <div class="settings-section">
            <div class="section-header">
                <div class="section-title-group">
                    <div class="section-icon tariff">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <h2>Tarifa de Energía</h2>
                        <p>Define el valor del kWh en COP para calcular costos en tiempo real</p>
                    </div>
                </div>
                <button class="btn-action" onclick="document.getElementById('newTariffModal').classList.add('show')">
                    <i class="fas fa-plus"></i> Nueva Tarifa
                </button>
            </div>

            <!-- Tarifa Activa Destacada -->
            <?php if ($activeTariff): ?>
                <div class="active-tariff-card">
                    <div class="tariff-badge">TARIFA ACTIVA</div>
                    <div class="tariff-main">
                        <div class="tariff-name"><?php echo htmlspecialchars($activeTariff['name']); ?></div>
                        <div class="tariff-rate">
                            <span class="rate-symbol">$</span>
                            <span class="rate-value"><?php echo number_format($activeTariff['rate_per_kwh'], 2); ?></span>
                            <span class="rate-unit">COP / kWh</span>
                        </div>
                    </div>
                    <div class="tariff-dates">
                        <?php if ($activeTariff['start_date']): ?>
                            <span><i class="fas fa-calendar-alt"></i> Desde: <?php echo format_date($activeTariff['start_date'], 'd/m/Y'); ?></span>
                        <?php endif; ?>
                        <?php if ($activeTariff['end_date']): ?>
                            <span><i class="fas fa-calendar-alt"></i> Hasta: <?php echo format_date($activeTariff['end_date'], 'd/m/Y'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Sin tarifa configurada</h3>
                    <p>Agrega una tarifa para ver el costo del consumo en tiempo real</p>
                </div>
            <?php endif; ?>

            <!-- Lista de Tarifas -->
            <?php if (!empty($tariffs)): ?>
                <div class="tariff-list">
                    <h3 class="list-title">Historial de Tarifas</h3>
                    <?php foreach ($tariffs as $t): ?>
                        <div class="tariff-item <?php echo $t['is_active'] ? 'active' : ''; ?>">
                            <div class="tariff-item-info">
                                <span class="tariff-item-name"><?php echo htmlspecialchars($t['name']); ?></span>
                                <span class="tariff-item-rate"><?php echo format_cop($t['rate_per_kwh']); ?> / kWh</span>
                                <?php if ($t['is_active']): ?>
                                    <span class="badge-active">Activa</span>
                                <?php endif; ?>
                            </div>
                            <div class="tariff-item-actions">
                                <button class="btn-icon edit" onclick="editTariff(<?php echo htmlspecialchars(json_encode($t)); ?>)" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST" action="<?php echo url('tariffs/delete'); ?>" class="inline-form" 
                                      onsubmit="return confirm('¿Eliminar esta tarifa?')">
                                    <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                    <button type="submit" class="btn-icon delete" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ========================================
             SECCIÓN: MI DISPOSITIVO (HARDWARE ID)
             ======================================== -->
        <div class="settings-section">
            <div class="section-header">
                <div class="section-title-group">
                    <div class="section-icon device">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div>
                        <h2>Mi Monitor ESP32</h2>
                        <p>Identificación física y estado del hardware</p>
                    </div>
                </div>
            </div>

            <?php if ($device): ?>
                <div class="api-key-card">
                    <div class="api-key-header">
                        <h3><i class="fas fa-id-card"></i> Hardware ID (MAC)</h3>
                        <span class="api-key-hint">Identificador único de tu hardware físico</span>
                    </div>
                    <div class="api-key-display">
                        <code id="hwIdValue"><?php echo htmlspecialchars($device['hardware_id'] ?? 'N/A'); ?></code>
                        <button class="btn-copy" onclick="copyHwId()" title="Copiar">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <div class="api-key-footer">
                        <div class="status-indicator">
                            <?php 
                                $isOnline = $device['last_seen'] && (time() - strtotime($device['last_seen']) < 30);
                            ?>
                            <span class="dot <?php echo $isOnline ? 'online' : 'offline'; ?>"></span>
                            <?php echo $isOnline ? 'En línea' : 'Desconectado (Últ. vez: ' . date('H:i', strtotime($device['last_seen'])) . ')'; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="<?php echo url('settings/device'); ?>" class="device-form">
                    <input type="hidden" name="id" value="<?php echo $device['id']; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="device_name">Nombre Personalizado</label>
                            <input type="text" id="device_name" name="device_name" 
                                   value="<?php echo htmlspecialchars($device['device_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="relay_default">Relay por defecto</label>
                            <select id="relay_default" name="relay_default">
                                <option value="ON" <?php echo $device['relay_default'] === 'ON' ? 'selected' : ''; ?>>Encendido (ON)</option>
                                <option value="OFF" <?php echo $device['relay_default'] === 'OFF' ? 'selected' : ''; ?>>Apagado (OFF)</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
                <div style="margin-top: 1rem;">
                    <form method="POST" action="<?php echo url('settings/reset-energy'); ?>" onsubmit="return confirm('¿Estás seguro de querer resetear a 0 el consumo de energía en el hardware?');">
                        <input type="hidden" name="hardware_id" value="<?php echo htmlspecialchars($device['hardware_id']); ?>">
                        <button type="submit" class="btn-primary" style="background-color: #ef4444;">
                            <i class="fas fa-trash-can"></i> Resetear Energía (kWh)
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-plug-circle-exclamation"></i>
                    <h3>No tienes un dispositivo vinculado</h3>
                    <p>Selecciona un dispositivo de la lista de detectados abajo para comenzar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================
         SECCIÓN: DISPOSITIVOS DETECTADOS (CLAIM)
         ======================================== -->
    <div class="settings-section" style="margin-top: 2rem;">
        <div class="section-header">
            <div class="section-title-group">
                <div class="section-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color:#fff; width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-search"></i>
                </div>
                <div>
                    <h2>Dispositivos Detectados</h2>
                    <p>Dispositivos ESP32 que han enviado datos recientemente pero no tienen dueño</p>
                </div>
            </div>
        </div>

        <?php if (!empty($unclaimedDevices)): ?>
            <div class="tariff-list">
                <?php foreach ($unclaimedDevices as $ud): ?>
                    <div class="tariff-item" style="display:flex; align-items:center; justify-content:space-between; padding: 1.25rem;">
                        <div class="dev-info">
                            <div style="font-weight: 600; font-size: 1rem; color: #fff;">
                                <i class="fas fa-esp32"></i> <?php echo htmlspecialchars($ud['hardware_id']); ?>
                            </div>
                            <div style="font-size: 0.8rem; opacity: 0.7;">
                                <i class="fas fa-clock"></i> Última actividad: <?php echo date('H:i:s d/m/Y', strtotime($ud['last_seen'])); ?>
                            </div>
                        </div>
                        <form method="POST" action="<?php echo url('settings/claim-device'); ?>">
                            <input type="hidden" name="hardware_id" value="<?php echo htmlspecialchars($ud['hardware_id']); ?>">
                            <button type="submit" class="btn-action" style="background: #22c55e; border-color: #22c55e;">
                                <i class="fas fa-link"></i> Vincular a mi cuenta
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 2rem;">
                <div class="pulse-loader" style="margin-bottom: 1rem;"></div>
                <h3>Buscando dispositivos...</h3>
                <p>Asegúrate de que tu ESP32 esté encendido y enviando datos al servidor.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================
         SECCIÓN: ACCESO COMPARTIDO (POR HARDWARE ID)
         ======================================== -->
    <div class="settings-section" style="margin-top: 2rem;">
        <div class="section-header">
            <div class="section-title-group">
                <div class="section-icon" style="background: linear-gradient(135deg, #7c3aed, #a78bfa); color:#fff; width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h2>Acceso Compartido</h2>
                    <p>Ver datos de otro monitor usando su Hardware ID</p>
                </div>
            </div>
        </div>

        <div class="api-key-card" style="margin-bottom:1.5rem;">
            <p style="font-size: 0.9rem; margin-bottom: 1rem; opacity: 0.8;">Ingresa el Hardware ID que te compartió el propietario del dispositivo.</p>
            <form method="POST" action="<?php echo url('settings/link-device'); ?>" style="display:flex;gap:.75rem;">
                <input type="text" name="shared_hardware_id"
                        placeholder="Ej: AA:BB:CC:DD:EE:FF"
                        style="flex:1; padding:.65rem 1rem; border:1.5px solid #334155; border-radius:8px; background:#0f172a; color:#fff;"
                        required>
                <button type="submit" class="btn-primary" style="white-space:nowrap;">
                    <i class="fas fa-plug"></i> Vincular Acceso
                </button>
            </form>
        </div>

        <?php if (!empty($sharedDevices)): ?>
            <div class="tariff-list">
                <?php foreach ($sharedDevices as $sd): ?>
                    <div class="tariff-item" style="display:flex;align-items:center;gap:1rem;">
                        <div style="width:10px;height:10px;border-radius:50%;background:#22c55e;"></div>
                        <div style="flex:1;">
                            <div style="font-weight:600;"><?php echo htmlspecialchars($sd['device_name']); ?></div>
                            <div style="font-size:0.75rem; opacity: 0.6;">Hardware ID: <?php echo htmlspecialchars($sd['hardware_id']); ?></div>
                        </div>
                        <form method="POST" action="<?php echo url('settings/unlink-device'); ?>">
                            <input type="hidden" name="hardware_id" value="<?php echo htmlspecialchars($sd['hardware_id'] ?? ''); ?>">
                            <button type="submit" class="btn-icon delete"><i class="fas fa-unlink"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Nueva Tarifa -->
<div class="modal-overlay" id="newTariffModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle"></i> Nueva Tarifa</h3>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="<?php echo url('tariffs/create'); ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label for="new_name">Nombre</label>
                    <input type="text" id="new_name" name="name" value="Tarifa Principal" required>
                </div>
                <div class="form-group">
                    <label for="new_rate">Valor kWh en COP</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-dollar-sign"></i></span>
                        <input type="number" id="new_rate" name="rate" step="0.01" min="0.01" placeholder="Ej: 850.00" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_start">Desde (opcional)</label>
                        <input type="date" id="new_start" name="start_date">
                    </div>
                    <div class="form-group">
                        <label for="new_end">Hasta (opcional)</label>
                        <input type="date" id="new_end" name="end_date">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('show')">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Crear Tarifa</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Tarifa -->
<div class="modal-overlay" id="editTariffModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Tarifa</h3>
            <button class="modal-close" onclick="this.closest('.modal-overlay').classList.remove('show')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="<?php echo url('tariffs/update'); ?>">
            <input type="hidden" id="edit_id" name="id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_name">Nombre</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit_rate">Valor kWh en COP</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-dollar-sign"></i></span>
                        <input type="number" id="edit_rate" name="rate" step="0.01" min="0.01" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="edit_active" name="is_active" value="1">
                        <span class="checkmark"></span>
                        Marcar como tarifa activa
                    </label>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_start">Desde</label>
                        <input type="date" id="edit_start" name="start_date">
                    </div>
                    <div class="form-group">
                        <label for="edit_end">Hasta</label>
                        <input type="date" id="edit_end" name="end_date">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="this.closest('.modal-overlay').classList.remove('show')">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editTariff(tariff) {
    document.getElementById('edit_id').value = tariff.id;
    document.getElementById('edit_name').value = tariff.name;
    document.getElementById('edit_rate').value = tariff.rate_per_kwh;
    document.getElementById('edit_active').checked = tariff.is_active == 1;
    document.getElementById('edit_start').value = tariff.start_date || '';
    document.getElementById('edit_end').value = tariff.end_date || '';
    document.getElementById('editTariffModal').classList.add('show');
}

function copyApiKey() {
    const text = document.getElementById('apiKeyValue') ? document.getElementById('apiKeyValue').textContent : '';
    if(!text) return;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.btn-copy');
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
    });
}

function copyHwId() {
    const text = document.getElementById('hwIdValue').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.btn-copy');
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
