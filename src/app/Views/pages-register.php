<!-- src/app/Views/pages-register.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - EnergyMonitor</title>
    <meta name="description" content="Regístrate para monitorear tu consumo de energía eléctrica en tiempo real.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('css/energy.css'); ?>?v=<?php echo time(); ?>">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-hero">
            <div class="hero-content">
                <div class="hero-icon-group">
                    <div class="hero-icon pulse-glow">
                        <i class="fas fa-bolt"></i>
                    </div>
                </div>
                <h1>EnergyMonitor</h1>
                <p>Tu aliado en el control inteligente de energía</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="stat-value">24/7</div>
                        <div class="stat-label">Monitoreo</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-value">5s</div>
                        <div class="stat-label">Actualización</div>
                    </div>
                    <div class="hero-stat">
                        <div class="stat-value">COP</div>
                        <div class="stat-label">Costo Real</div>
                    </div>
                </div>
            </div>
            <div class="hero-wave"></div>
        </div>

        <div class="auth-form-panel">
            <div class="auth-form-wrapper">
                <div class="auth-form-header">
                    <div class="mobile-logo">
                        <i class="fas fa-bolt"></i>
                        <span>EnergyMonitor</span>
                    </div>
                    <h2>Crear Cuenta</h2>
                    <p>Completa tus datos para empezar a monitorear</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo url('register'); ?>" class="auth-form" id="registerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <div class="input-group">
                                <span class="input-icon"><i class="fas fa-user"></i></span>
                                <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" 
                                       value="<?php echo htmlspecialchars($_SESSION['form_data']['nombre'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellido</label>
                            <div class="input-group">
                                <span class="input-icon"><i class="fas fa-user"></i></span>
                                <input type="text" id="apellido" name="apellido" placeholder="Tu apellido" 
                                       value="<?php echo htmlspecialchars($_SESSION['form_data']['apellido'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono <span class="optional">(opcional)</span></label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-phone"></i></span>
                            <input type="text" id="telefono" name="telefono" placeholder="+57 300 000 0000" 
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['telefono'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-envelope"></i></span>
                            <input type="email" id="email" name="email" placeholder="tu@correo.com" 
                                   value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <div class="input-group">
                                <span class="input-icon"><i class="fas fa-lock"></i></span>
                                <input type="password" id="password" name="password" placeholder="Mín. 6 caracteres" required minlength="6">
                                <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirmar</label>
                            <div class="input-group">
                                <span class="input-icon"><i class="fas fa-lock"></i></span>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repetir" required minlength="6">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary-auth" id="registerBtn">
                        <span>Crear Cuenta</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-footer">
                    <p>¿Ya tienes cuenta? <a href="<?php echo url('login'); ?>">Iniciar sesión</a></p>
                </div>
            </div>
        </div>
    </div>

    <?php unset($_SESSION['form_data']); ?>
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>