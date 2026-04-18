<?php
// src/app/Views/perfil.php
$pageTitle = 'Mi Perfil';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<div class="settings-page">
    <div class="page-header">
        <h1><i class="fas fa-user-circle"></i> Mi Perfil</h1>
        <p>Configura tu información personal y credenciales de acceso</p>
    </div>

    <!-- Mensajes de flash -->
    <?php include __DIR__ . '/includes/alertEvent.php'; ?>
    <?php if (isset($flashError)): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $flashError; ?></div>
    <?php endif; ?>
    <?php if (isset($flashSuccess)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $flashSuccess; ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- ========================================
             SECCIÓN: DATOS DE USUARIO 
             ======================================== -->
        <div class="settings-section">
            <div class="section-header">
                <div class="section-title-group">
                    <div class="section-icon" style="background: rgba(139,92,246,0.12); color: var(--accent-purple);">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h2>Información General</h2>
                        <p>Tus datos principales de contacto</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="<?php echo url('perfil/update'); ?>" class="device-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre(s)</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido(s)</label>
                        <input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($user['apellido'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled title="El correo no se puede cambiar">
                        <span class="form-hint">Tu correo electrónico es tu usuario único y no puede cambiarse.</span>
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </form>
        </div>

        <!-- ========================================
             SECCIÓN: SEGURIDAD
             ======================================== -->
        <div class="settings-section">
            <div class="section-header">
                <div class="section-title-group">
                    <div class="section-icon" style="background: rgba(239,68,68,0.12); color: var(--accent-red);">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h2>Seguridad & Contraseña</h2>
                        <p>Modifica tu contraseña de acceso</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="<?php echo url('perfil/changePassword'); ?>" class="device-form">
                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1; max-width: 50%;">
                        <label for="current_password">Contraseña actual</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nueva contraseña</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmar nueva contraseña</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn-secondary">
                    <i class="fas fa-key"></i> Actualizar Contraseña
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>