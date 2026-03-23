<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2">Mi Perfil</h1>
        <p class="text-muted">Gestiona tu información personal y preferencias.</p>
    </div>
</div>

<div class="row justify-content-center">
    <!-- Columna Izquierda: Foto y Resumen -->
    <div class="col-lg-4 mb-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <div class="position-relative d-inline-block mb-3">
                    <?php if(!empty($user['foto'])): ?>
                        <img src="<?php echo upload_url($user['foto']); ?>" alt="Foto de perfil" class="rounded-circle border border-3 border-primary" style="width: 120px; height: 120px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 120px; height: 120px; font-size: 3rem;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle" onclick="document.getElementById('fotoUpload').click()">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                
                <h3 class="mb-1"><?php echo htmlspecialchars(($user['nombre'] ?? 'Usuario') . ' ' . ($user['apellido'] ?? '')); ?></h3>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                <div class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                    <i class="fas fa-check-circle me-1"></i> Cuenta Activa
                </div>
                
                <hr class="my-4">
                <div class="text-start">
                    <div class="mb-2"><strong class="text-dark">Miembro desde:</strong> <span class="text-muted float-end"><?php echo !empty($user['created_at']) ? date('M Y', strtotime($user['created_at'])) : 'N/A'; ?></span></div>
                    <div><strong class="text-dark">Último acceso:</strong> <span class="text-muted float-end"><?php echo !empty($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'N/A'; ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Formularios de Edición -->
    <div class="col-lg-8">
        <!-- Formulario Datos Personales -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Información Personal</h4>
            </div>
            <div class="card-body">
                <form action="perfil/actualizar" method="POST" enctype="multipart/form-data">
                    <!-- Input file oculto para la foto -->
                    <input type="file" name="foto" id="fotoUpload" style="display:none;" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this)">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label fw-semibold">Nombre*</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="nombre" id="nombre" required value="<?php echo htmlspecialchars($user['nombre'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellido" class="form-label fw-semibold">Apellido*</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="apellido" id="apellido" required value="<?php echo htmlspecialchars($user['apellido'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-text"><i class="fas fa-lock me-1"></i> El correo no puede ser modificado por razones de seguridad.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label fw-semibold">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" name="telefono" id="telefono" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edad" class="form-label fw-semibold">Edad</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                <input type="number" class="form-control" name="edad" id="edad" min="16" max="100" value="<?php echo $user['edad'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="moneda" class="form-label fw-semibold">Moneda de visualización (Símbolo)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-coins"></i></span>
                            <input type="text" class="form-control" name="moneda" id="moneda" value="<?php echo htmlspecialchars($user['moneda'] ?? 'COP'); ?>" maxlength="3" placeholder="COP, USD, EUR...">
                        </div>
                        <div class="form-text">Afecta cómo se muestran los símbolos de moneda en toda la aplicación.</div>
                    </div>

                    <!-- Configuración del Presupuesto Inteligente -->
                    <h5 class="mt-4 mb-3 pb-2 border-bottom"><i class="fas fa-brain text-primary me-2"></i> Presupuesto Inteligente</h5>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ingreso_mensual" class="form-label fw-semibold">Ingreso Mensual (Aprox.)</label>
                            <div class="input-group">
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                                <input type="text" class="form-control" name="ingreso_mensual" id="ingreso_mensual" value="<?php echo number_format($user['ingreso_mensual'] ?? 0, 0, ',', '.'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dia_pago" class="form-label fw-semibold">Día de Pago habitual</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                                <input type="number" class="form-control" name="dia_pago" id="dia_pago" min="1" max="31" value="<?php echo $user['dia_pago'] ?? 1; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="porcentaje_ahorro" class="form-label fw-semibold">Porcentaje de ahorro recomendado (%)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                            <input type="number" class="form-control" name="porcentaje_ahorro" id="porcentaje_ahorro" min="0" max="100" value="<?php echo $user['porcentaje_ahorro'] ?? 20; ?>">
                        </div>
                        <div class="form-text">Este porcentaje se usará para separar dinero destinado a tus metas y emergencias antes de gastar.</div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Guardar Preferencias
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Formulario Cambio Contraseña -->
        <div class="card shadow-sm border-danger" style="border-left: 4px solid #dc3545;">
            <div class="card-header bg-danger-subtle">
                <h4 class="mb-0 text-danger"><i class="fas fa-key me-2"></i>Cambiar Contraseña</h4>
            </div>
            <div class="card-body">
                <form action="perfil/cambiar-password" method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label fw-semibold">Contraseña Actual*</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-unlock-alt"></i></span>
                            <input type="password" class="form-control" name="current_password" id="current_password" required placeholder="Ingresa tu contraseña actual">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label fw-semibold">Nueva Contraseña*</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="new_password" id="new_password" required placeholder="Mínimo 6 caracteres" minlength="6">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label fw-semibold">Confirmar Nueva Contraseña*</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required placeholder="Repite la contraseña" minlength="6">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-shield-alt me-2"></i>Actualizar Contraseña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var container = document.querySelector('.position-relative.d-inline-block');
            var img = container.querySelector('img');
            
            if (img) {
                img.src = e.target.result;
            } else {
                // Si no hay imagen (solo iniciales), ocultar el div de iniciales
                var initialsDiv = container.querySelector('div.bg-primary');
                if(initialsDiv) initialsDiv.style.display = 'none';
                
                img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Foto de perfil';
                img.className = 'rounded-circle border border-3 border-primary';
                img.style.width = '120px';
                img.style.height = '120px';
                img.style.objectFit = 'cover';
                
                // Insertar al principio
                container.insertBefore(img, container.firstChild);
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

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

// Formateo de ingreso mensual
const ingresoInput = document.getElementById('ingreso_mensual');
if(ingresoInput) {
    ingresoInput.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '');
        if (val !== '') {
            val = parseInt(val);
            this.value = new Intl.NumberFormat('es-CO').format(val);
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
