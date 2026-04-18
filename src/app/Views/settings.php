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
             SECCIÓN: CONFIGURACIÓN DEL DISPOSITIVO
             ======================================== -->
        <div class="settings-section">
            <div class="section-header">
                <div class="section-title-group">
                    <div class="section-icon device">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div>
                        <h2>Dispositivo ESP32</h2>
                        <p>Configuración del monitor de energía y conexión API</p>
                    </div>
                </div>
            </div>

            <!-- API Key -->
            <div class="api-key-card">
                <div class="api-key-header">
                    <h3><i class="fas fa-key"></i> API Key</h3>
                    <span class="api-key-hint">Usa esta clave en tu ESP32 para enviar datos</span>
                </div>
                <div class="api-key-display">
                    <code id="apiKeyValue"><?php echo $device['api_key'] ?? 'Sin clave'; ?></code>
                    <button class="btn-copy" onclick="copyApiKey()" title="Copiar">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <div class="api-key-footer">
                    <div class="api-endpoint">
                        <span class="endpoint-label">Endpoint:</span>
                        <code>POST <?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?><?php echo BASE_URL; ?>/api/save</code>
                    </div>
                    <form method="POST" action="<?php echo url('settings/regenerate-key'); ?>" 
                          onsubmit="return confirm('¿Regenerar la API Key? \nDeberás actualizar el código de tu ESP32.')">
                        <button type="submit" class="btn-warning-small">
                            <i class="fas fa-sync"></i> Regenerar Key
                        </button>
                    </form>
                </div>
            </div>

            <!-- Configuración del dispositivo -->
            <form method="POST" action="<?php echo url('settings/device'); ?>" class="device-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="device_name">Nombre del Dispositivo</label>
                        <input type="text" id="device_name" name="device_name" 
                               value="<?php echo htmlspecialchars($device['device_name'] ?? 'Monitor PZEM-004T'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="relay_default">Relay por defecto</label>
                        <select id="relay_default" name="relay_default">
                            <option value="ON" <?php echo ($device['relay_default'] ?? 'ON') === 'ON' ? 'selected' : ''; ?>>Encendido (ON)</option>
                            <option value="OFF" <?php echo ($device['relay_default'] ?? 'ON') === 'OFF' ? 'selected' : ''; ?>>Apagado (OFF)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="max_current">Corriente Máxima (A)</label>
                        <input type="number" id="max_current" name="max_current" step="0.01" 
                               value="<?php echo $device['max_current'] ?? 100; ?>">
                    </div>
                    <div class="form-group">
                        <label for="max_power">Potencia Máxima (W)</label>
                        <input type="number" id="max_power" name="max_power" step="0.01" 
                               value="<?php echo $device['max_power'] ?? 22000; ?>">
                    </div>
                    <div class="form-group">
                        <label for="alert_threshold">Alerta de Consumo (kWh/día)</label>
                        <input type="number" id="alert_threshold" name="alert_threshold" step="0.01" 
                               value="<?php echo $device['alert_threshold'] ?? 0; ?>">
                        <span class="form-hint">0 = Sin alerta</span>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Guardar Configuración
                </button>
            </form>

            <!-- Código de ejemplo para el ESP32 -->
            <div class="code-example">
                <h3><i class="fas fa-code"></i> Código para tu ESP32</h3>
                <p>Modifica la siguiente línea en tu archivo <code>Proyecto_Energia.ino</code>:</p>
                <pre><code>// Cambia la URL del webhook por:
const char* webhookUrl = "<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?><?php echo BASE_URL; ?>/api/save";

// En la función enviarDatosWebhook(), agrega el header:
http.addHeader("X-API-KEY", "<?php echo $device['api_key'] ?? 'TU_API_KEY'; ?>");</code></pre>
            </div>
        </div>
    </div>

    <!-- ========================================
         SECCIÓN: ACCESO COMPARTIDO
         Permite ingresar la API key de otro dispositivo
         para ver sus datos en tiempo real.
         ======================================== -->
    <div class="settings-section">
        <div class="section-header">
            <div class="section-title-group">
                <div class="section-icon" style="background: linear-gradient(135deg, #7c3aed, #a78bfa); color:#fff; width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-share-alt"></i>
                </div>
                <div>
                    <h2>Acceso Compartido</h2>
                    <p>Vincula la API Key de otro dispositivo para ver sus datos en tiempo real</p>
                </div>
            </div>
        </div>

        <!-- Formulario para vincular nuevo dispositivo -->
        <div class="api-key-card" style="margin-bottom:1.5rem;">
            <div class="api-key-header">
                <h3><i class="fas fa-link"></i> Vincular Dispositivo</h3>
                <span class="api-key-hint">Ingresa la API Key que te compartió el propietario del dispositivo</span>
            </div>
            <?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div style="background:#fee2e2;border:1px solid #f87171;color:#991b1b;padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['success'])): ?>
                <div style="background:#d1fae5;border:1px solid #34d399;color:#065f46;padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.9rem;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?php echo url('settings/link-device'); ?>" style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;">
                <input type="text" name="shared_api_key"
                       placeholder="Ej: 667f982a2c7ad0d77788f848..."
                       style="flex:1;min-width:200px;padding:.65rem 1rem;border:1.5px solid var(--border-color,#334155);border-radius:8px;background:var(--input-bg,#0f172a);color:inherit;font-family:monospace;font-size:.85rem;"
                       required>
                <button type="submit" class="btn-primary" style="white-space:nowrap;">
                    <i class="fas fa-plug"></i> Vincular
                </button>
            </form>
        </div>

        <!-- Lista de dispositivos compartidos vinculados -->
        <?php if (!empty($sharedDevices)): ?>
            <div class="tariff-list">
                <h3 class="list-title">Dispositivos Vinculados</h3>
                <?php foreach ($sharedDevices as $sd): ?>
                    <?php
                        $lsTime = $sd['last_seen'] ? strtotime($sd['last_seen']) : null;
                        $isOnline = $lsTime && (time() - $lsTime < 30);
                    ?>
                    <div class="tariff-item" style="display:flex;align-items:center;gap:1rem;">
                        <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $isOnline ? '#22c55e' : '#ef4444'; ?>;flex-shrink:0;"></span>
                        <div class="tariff-item-info" style="flex:1;">
                            <span class="tariff-item-name"><?php echo htmlspecialchars($sd['device_name']); ?></span>
                            <span class="tariff-item-rate" style="font-size:.78rem;">
                                Propietario: <?php echo htmlspecialchars($sd['owner_name'] . ' &lt;' . $sd['owner_email'] . '&gt;'); ?>
                            </span>
                            <span class="tariff-item-rate" style="font-size:.75rem;opacity:.6;">
                                <?php echo $isOnline ? '<i class="fas fa-circle" style="color:#22c55e"></i> En línea' : ($lsTime ? 'Última vez: ' . date('d/m/Y H:i', $lsTime) : 'Sin conexión'); ?>
                            </span>
                        </div>
                        <div class="tariff-item-actions">
                            <form method="POST" action="<?php echo url('settings/unlink-device'); ?>"
                                  onsubmit="return confirm('¿Quitar acceso a este dispositivo?')">
                                <input type="hidden" name="api_key" value="<?php echo htmlspecialchars($sd['api_key']); ?>">
                                <button type="submit" class="btn-icon delete" title="Desvincular">
                                    <i class="fas fa-unlink"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:1.5rem;">
                <i class="fas fa-share-alt"></i>
                <h3>Sin dispositivos vinculados</h3>
                <p>Ingresa una API Key para acceder a los datos de otro dispositivo</p>
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
    const text = document.getElementById('apiKeyValue').textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('.btn-copy');
        btn.innerHTML = '<i class="fas fa-check"></i>';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i>'; }, 2000);
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
