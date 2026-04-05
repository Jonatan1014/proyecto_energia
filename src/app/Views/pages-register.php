<!-- src/app/Views/pages-register.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - AlcanciaApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Crear Cuenta</h2>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        <form method="POST" action="register">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo $_SESSION['form_data']['nombre'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo $_SESSION['form_data']['apellido'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo $_SESSION['form_data']['telefono'] ?? ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo $_SESSION['form_data']['email'] ?? ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Registrarse</button>
                        </form>
                        <p class="text-center mt-3">¿Ya tienes cuenta? <a href="login">Inicia sesión aquí</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['form_data']); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>