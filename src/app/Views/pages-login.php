<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - FinanzApp</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .auth-left {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-right {
            padding: 3rem 2rem;
        }
        .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #4f46e5;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card row g-0">
            <!-- Lado izquierdo: Características -->
            <div class="auth-left col-lg-5 d-none d-lg-flex">
                <div>
                    <div class="logo mb-4">
                        <i class="fas fa-wallet"></i>
                        FinanzApp
                    </div>
                    <h2 class="mb-4">Toma el control de tus finanzas</h2>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Control Total</h5>
                            <p class="mb-0 opacity-75">Visualiza tus gastos e ingresos con gráficos interactivos.</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Metas Reales</h5>
                            <p class="mb-0 opacity-75">Ahorra para lo que de verdad importa.</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">Gastos Compartidos</h5>
                            <p class="mb-0 opacity-75">Divide cuentas con amigos y familiares.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lado derecho: Formulario -->
            <div class="auth-right col-lg-7 col-12">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark">Bienvenido de nuevo</h3>
                    <p class="text-muted">Ingresa tus credenciales para continuar</p>
                </div>
                
                <form action="login" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" required placeholder="tu@correo.com">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required placeholder="Ingresa tu contraseña">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">
                                Recuérdame
                            </label>
                        </div>
                        <a href="#" class="text-decoration-none">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        Iniciar Sesión <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                    
                    <div class="text-center">
                        <p class="mb-0">¿No tienes una cuenta? <a href="register" class="text-decoration-none fw-semibold">Regístrate aquí</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
