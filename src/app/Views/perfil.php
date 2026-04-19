<!-- src/app/Views/perfil.php -->
<?php
$nombre = (string)($user['nombre'] ?? '');
$apellido = (string)($user['apellido'] ?? '');
$telefono = (string)($user['telefono'] ?? '');
$edad = isset($user['edad']) ? (int)$user['edad'] : '';
$foto = (string)($user['foto'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - AlcanciaApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Estilos Base */
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .perfil-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .perfil-card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .perfil-card:hover {
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .perfil-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
        }

        .perfil-body {
            padding: 2rem;
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e9ecef;
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
        }

        .form-control:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.15);
            outline: none;
        }

        .form-row-mobile {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .alert {
            border-radius: 1rem;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .btn-submit {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            cursor: pointer;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(79, 70, 229, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-back {
            background: transparent;
            border: 2px solid #4f46e5;
            color: #4f46e5;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-back:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-2px);
        }

        .foto-preview {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            margin-bottom: 1rem;
        }

        .foto-preview img {
            border-radius: 0.75rem;
            border: 2px solid #e9ecef;
            max-width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .buttons-group {
            display: flex;
            gap: 1rem;
            flex-direction: column;
            margin-top: 2rem;
        }

        /* Tablet Responsivo */
        @media (min-width: 576px) {
            .perfil-body {
                padding: 2.5rem;
            }

            .perfil-title {
                font-size: 2.5rem;
            }

            .form-row-mobile {
                flex-direction: row;
            }

            .form-row-mobile > div {
                flex: 1;
            }

            .buttons-group {
                flex-direction: row;
            }

            .buttons-group button,
            .buttons-group a {
                flex: 1;
            }
        }

        /* Desktop */
        @media (min-width: 768px) {
            .perfil-container {
                padding: 2rem;
            }

            .perfil-card {
                max-width: 600px;
                width: 100%;
            }
        }

        /* Móvil Extra Pequeño */
        @media (max-width: 375px) {
            .perfil-title {
                font-size: 1.5rem;
            }

            .perfil-body {
                padding: 1.25rem;
            }

            .form-label {
                font-size: 0.9rem;
            }

            .form-control {
                font-size: 16px; /* Previene zoom en iOS */
            }

            .btn-submit,
            .btn-back {
                padding: 0.6rem 1rem;
                font-size: 0.95rem;
            }
        }
    </link>
</head>
<body class="bg-light">
    <div class="perfil-container">
        <div class="perfil-card">
            <div class="card-body perfil-body">
                        <div class="text-center mb-4">
                            <h2 class="perfil-title"><i class="bi bi-person-circle"></i> Mi Perfil</h2>
                        </div>
                        
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>

                        <!-- Sección de Información Personal -->
                        <form method="POST" action="perfil/update" enctype="multipart/form-data">
                            <h3 class="form-section-title"><i class="bi bi-info-circle"></i> Información Personal</h3>
                            
                            <div class="form-row-mobile">
                                <div>
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($nombre); ?>" required>
                                </div>
                                <div>
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($apellido); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" id="telefono" name="telefono" class="form-control" value="<?php echo htmlspecialchars($telefono); ?>" placeholder="+57 3XX XXX XXXX">
                            </div>
                            
                            <div class="mt-3">
                                <label for="edad" class="form-label">Edad</label>
                                <input type="number" id="edad" name="edad" class="form-control" value="<?php echo $edad; ?>" min="18" max="120">
                            </div>

                            <!-- Foto de Perfil -->
                            <h3 class="form-section-title"><i class="bi bi-image"></i> Foto de Perfil</h3>
                            
                            <div class="foto-preview">
                                <?php if ($foto !== ''): ?>
                                    <img src="/<?php echo htmlspecialchars($foto); ?>" alt="Foto de Perfil">
                                <?php endif; ?>
                                <div style="flex: 1;">
                                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                                    <small class="text-muted">JPG, PNG o GIF (máx. 5MB)</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-submit"><i class="bi bi-check"></i> Guardar Cambios</button>
                        </form>

                        <!-- Sección de Seguridad -->
                        <form method="POST" action="perfil/changePassword" class="mt-4">
                            <h3 class="form-section-title"><i class="bi bi-shield-lock"></i> Cambiar Contraseña</h3>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Contraseña Actual</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-row-mobile">
                                <div>
                                    <label for="new_password" class="form-label">Nueva Contraseña</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                </div>
                                <div>
                                    <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-submit mt-3"><i class="bi bi-lock"></i> Cambiar Contraseña</button>
                        </form>

                        <!-- Botones de Acción -->
                        <div class="buttons-group">
                            <a href="dashboard" class="btn btn-back"><i class="bi bi-arrow-left"></i> Volver al Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
