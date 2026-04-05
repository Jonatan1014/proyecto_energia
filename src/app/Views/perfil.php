<!-- src/app/Views/perfil.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - AlcanciaApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Mi Perfil</h2>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="perfil/update" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($user['apellido']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($user['telefono']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="edad" class="form-label">Edad</label>
                                <input type="number" id="edad" name="edad" class="form-control" value="<?php echo $user['edad']; ?>" min="18">
                            </div>
                            <div class="mb-3">
                                <label for="foto" class="form-label">Foto de Perfil</label>
                                <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                                <?php if ($user['foto']): ?>
                                    <img src="/<?php echo $user['foto']; ?>" alt="Foto" class="img-thumbnail mt-2" width="100">
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary">Actualizar Perfil</button>
                        </form>

                        <hr class="my-4">

                        <h3>Cambiar Contraseña</h3>
                        <form method="POST" action="perfil/changePassword">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Contraseña Actual</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nueva Contraseña</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-secondary">Cambiar Contraseña</button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="dashboard" class="btn btn-outline-primary">Volver al Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>