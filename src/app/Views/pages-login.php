<!-- src/app/Views/pages-login.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - EnergyMonitor</title>
    <meta name="description" content="Accede a tu sistema de monitoreo de energía eléctrica en tiempo real.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset_url('css/energy.css'); ?>?v=<?php echo time(); ?>">
</head>
<body class="auth-body">
    <div class="auth-container">
        <!-- Panel decorativo izquierdo -->
        <div class="auth-hero">
            <div class="hero-content">
                <div class="hero-icon-group">
                    <div class="hero-icon pulse-glow">
                        <i class="fas fa-bolt"></i>
                    </div>
                </div>
                <h1>EnergyMonitor</h1>
                <p>Monitoreo inteligente de energía eléctrica en tiempo real</p>
                <div class="hero-features">
                    <div class="hero-feature">
                        <i class="fas fa-chart-line"></i>
                        <span>Gráficas en tiempo real</span>
                    </div>
                    <div class="hero-feature">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Costo en COP al instante</span>
                    </div>
                    <div class="hero-feature">
                        <i class="fas fa-microchip"></i>
                        <span>Integración PZEM-004T</span>
                    </div>
                    <div class="hero-feature">
                        <i class="fas fa-bell"></i>
                        <span>Alertas de consumo</span>
                    </div>
                </div>
            </div>
            <div class="hero-wave"></div>
        </div>

        <!-- Panel del formulario -->
        <div class="auth-form-panel">
            <div class="auth-form-wrapper">
                <div class="auth-form-header">
                    <div class="mobile-logo">
                        <i class="fas fa-bolt"></i>
                        <span>EnergyMonitor</span>
                    </div>
                    <h2>Bienvenido</h2>
                    <p>Ingresa tus credenciales para acceder al sistema</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo url('login'); ?>" class="auth-form" id="loginForm">
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-envelope"></i></span>
                            <input type="email" id="email" name="email" placeholder="tu@correo.com" required autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-group">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkmark"></span>
                            Recordar sesión
                        </label>
                    </div>

                    <button type="submit" class="btn-primary-auth" id="loginBtn">
                        <span>Iniciar Sesión</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-footer">
                    <p>¿No tienes una cuenta? <a href="<?php echo url('register'); ?>">Crear cuenta</a></p>
                </div>
            </div>
        </div>
    </div>

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