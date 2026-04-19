<!-- src/app/Views/pages-register.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - AlcanciaApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Base */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .auth-container {
            padding: 1rem;
            width: 100%;
        }

        .auth-card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            background: white;
        }

        .auth-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 1.5rem;
            text-align: center;
            color: white;
        }

        .auth-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .auth-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .auth-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .auth-body {
            padding: 2rem 1.5rem;
        }

        .form-row {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-bottom: 0.5rem;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
            outline: none;
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 700;
            padding: 0.9rem;
            border-radius: 0.75rem;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 0.5rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .auth-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.95rem;
            color: #6c757d;
        }

        .auth-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .auth-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .alert {
            border-radius: 1rem;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        /* Móvil Extra Pequeño */
        @media (max-width: 375px) {
            body {
                padding: 0.5rem;
            }

            .auth-card-header {
                padding: 1.5rem 1rem;
            }

            .auth-icon {
                font-size: 2rem;
            }

            .auth-title {
                font-size: 1.5rem;
            }

            .auth-body {
                padding: 1.5rem 1rem;
            }

            .form-control {
                font-size: 16px; /* Previene zoom en iOS */
                margin-bottom: 0.5rem;
            }

            .form-row {
                gap: 0.75rem;
            }
        }

        /* Móvil */
        @media (max-width: 576px) {
            .auth-container {
                padding: 1rem;
            }

            .auth-card {
                border-radius: 1.25rem;
            }

            .auth-card-header {
                padding: 1.75rem 1.5rem;
            }

            .auth-body {
                padding: 1.75rem 1.5rem;
            }

            .form-row {
                flex-direction: column;
            }

            .form-row > div {
                width: 100%;
            }
        }

        /* Tablet */
        @media (min-width: 576px) {
            .auth-card {
                max-width: 500px;
                margin: 0 auto;
            }

            .auth-body {
                padding: 2.5rem 2rem;
            }

            .form-row {
                flex-direction: row;
            }

            .form-row > div {
                flex: 1;
            }
        }

        /* Desktop */
        @media (min-width: 768px) {
            .auth-card {
                max-width: 550px;
            }

            .auth-card-header {
                padding: 3rem 2rem;
            }

            .auth-body {
                padding: 3rem 2rem;
            }

            .auth-title {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-card-header">
                <div class="auth-icon"><i class="bi bi-person-plus-fill"></i></div>
                <h2 class="auth-title">Crear Cuenta</h2>
                <p class="auth-subtitle">Únete a AlcanciaApp hoy</p>
            </div>
            <div class="auth-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="register">
                    <div class="form-row">
                        <div>
                            <label for="nombre" class="form-label"><i class="bi bi-person-badge"></i> Nombre</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($_SESSION['form_data']['nombre'] ?? ''); ?>" placeholder="Tu nombre" required>
                        </div>
                        <div>
                            <label for="apellido" class="form-label"><i class="bi bi-person-badge"></i> Apellido</label>
                            <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($_SESSION['form_data']['apellido'] ?? ''); ?>" placeholder="Tu apellido" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telefono" class="form-label"><i class="bi bi-telephone"></i> Teléfono (opcional)</label>
                        <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($_SESSION['form_data']['telefono'] ?? ''); ?>" placeholder="+57 3XX XXX XXXX">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label"><i class="bi bi-envelope"></i> Correo Electrónico</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['form_data']['email'] ?? ''); ?>" placeholder="tu@correo.com" required>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <label for="password" class="form-label"><i class="bi bi-key"></i> Contraseña</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div>
                            <label for="confirm_password" class="form-label"><i class="bi bi-key"></i> Confirmar</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-register">
                        <i class="bi bi-person-plus"></i> Crear Cuenta
                    </button>
                </form>

                <p class="auth-link">
                    ¿Ya tienes cuenta? <a href="login">Inicia sesión aquí</a>
                </p>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['form_data']); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
