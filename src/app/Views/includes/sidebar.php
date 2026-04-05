<?php
// src/app/Views/includes/sidebar.php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$sidebarUser = isset($user) ? $user : ($_SESSION['user'] ?? null);

$alertasNoLeidas = 0;
?>
<aside class="d-flex flex-column flex-shrink-0 p-3 bg-white border-end shadow-sm sidebar" id="sidebar" style="width: 280px; min-height: 100vh;">
    <div class="sidebar-header d-flex justify-content-between align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none w-100">
        <a href="<?php echo url('dashboard'); ?>" class="logo d-flex align-items-center gap-2 text-decoration-none">
            <i class="fas fa-piggy-bank fs-4 text-primary"></i>
            <span class="fs-4 fw-bold text-primary">AlcanciaApp</span>
        </a>
        <button class="btn btn-sm btn-outline-secondary d-md-none mobile-toggle" id="mobileToggle">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <hr class="my-3">
    
    <nav class="sidebar-nav flex-grow-1 overflow-auto custom-scrollbar">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item mt-2">
                <span class="text-uppercase small fw-bold text-muted ps-3" style="font-size: 0.75rem;">Principal</span>
            </li>
            <li class="nav-item">
                <a href="<?php echo url('dashboard'); ?>" class="nav-link text-dark <?php echo strpos($currentPath, 'dashboard') !== false ? 'active bg-primary text-white' : 'hover-primary'; ?>" aria-current="page">
                    <i class="fas fa-chart-line me-2 text-center" style="width: 20px;"></i>
                    Resumen
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo url('perfil'); ?>" class="nav-link text-dark <?php echo strpos($currentPath, 'perfil') !== false ? 'active bg-primary text-white' : 'hover-primary'; ?>">
                    <i class="fas fa-user me-2 text-center" style="width: 20px;"></i>
                    Mi Perfil
                </a>
            </li>

            <li class="nav-item mt-3">
                <span class="text-uppercase small fw-bold text-muted ps-3" style="font-size: 0.75rem;">Alcancia IoT</span>
            </li>
            <li class="nav-item">
                <a href="<?php echo url('api/alcancia/status'); ?>" class="nav-link text-dark">
                    <i class="fas fa-server me-2 text-center" style="width: 20px;"></i>
                    Estado API
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo url('logout'); ?>" class="nav-link text-danger hover-primary">
                    <i class="fas fa-arrow-right-from-bracket me-2 text-center" style="width: 20px;"></i>
                    Cerrar Sesion
                </a>
            </li>
        </ul>
    </nav>
</aside>

<div class="main-content flex-grow-1 d-flex flex-column" id="mainContent" style="max-height: 100vh;">
    <header class="topbar sticky-top bg-white border-bottom shadow-sm px-3 py-2 d-flex align-items-center justify-content-between z-1" style="height: 70px;">
        <!-- Left Section: Mobile Toggle & Breadcrumbs -->
        <div class="d-flex align-items-center">
            <button id="sidebarToggle" class="btn btn-light d-md-none sidebar-toggle border me-3">
                <i class="fas fa-bars"></i>
            </button>
            <div class="d-none d-md-block">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="<?php echo url('dashboard'); ?>" class="text-decoration-none text-muted">Alcancia</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php
                            $pageTitles = [
                                'dashboard' => 'Resumen',
                                'perfil' => 'Mi Perfil',
                                'api' => 'API'
                            ];
                            $currentPage = 'Resumen';
                            foreach ($pageTitles as $key => $title) {
                                if (strpos($currentPath, $key) !== false) {
                                    $currentPage = $title;
                                    break;
                                }
                            }
                            echo $currentPage;
                            ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Center Section: Welcome Message -->
        <div class="d-none d-lg-block flex-grow-1 text-center mx-4">
            <div class="user-greeting">
                <span class="text-muted">¡Hola,</span>
                <strong class="text-primary ms-1"><?php echo htmlspecialchars(explode(' ', $sidebarUser['nombre'] ?? 'Usuario')[0]); ?>!</strong>
                <span class="text-muted ms-1">👋</span>
            </div>
        </div>

        <!-- Right Section: Notifications, Currency & Profile -->
        <div class="d-flex align-items-center gap-2">
            <!-- Currency Badge -->
            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 rounded-pill fs-6 fw-normal d-none d-md-inline-flex">
                <i class="fas fa-coins me-1"></i> <?php echo $sidebarUser['moneda'] ?? 'COP'; ?>
            </span>

            <!-- Notifications Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light position-relative border-0 p-2" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell fs-5 text-muted"></i>
                    <?php if(isset($alertasNoLeidas) && $alertasNoLeidas > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            <?php echo $alertasNoLeidas > 99 ? '99+' : $alertasNoLeidas; ?>
                            <span class="visually-hidden">alertas no leídas</span>
                        </span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="notificationsDropdown" style="min-width: 320px;">
                    <li>
                        <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notificaciones</span>
                            <?php if(isset($alertasNoLeidas) && $alertasNoLeidas > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $alertasNoLeidas; ?> nuevas</span>
                            <?php endif; ?>
                        </h6>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <!-- Aquí irían las notificaciones recientes -->
                    <li>
                        <a class="dropdown-item d-flex align-items-center py-3" href="<?php echo url('alertas'); ?>">
                            <div class="flex-shrink-0 me-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-bell"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 fw-medium">Ver todas las alertas</p>
                                <small class="text-muted">Gestiona tus notificaciones</small>
                            </div>
                            <i class="fas fa-chevron-right text-muted ms-2"></i>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light border-0 p-1 d-flex align-items-center" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <?php if(!empty($sidebarUser['foto'])): ?>
                            <img src="<?php echo upload_url($sidebarUser['foto']); ?>" alt="Perfil" width="36" height="36" class="rounded-circle border border-2 border-primary object-fit-cover">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center border border-2 border-white" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                <?php echo strtoupper(substr($sidebarUser['nombre'] ?? 'U', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-none d-md-block ms-2 text-start">
                            <div class="fw-medium text-dark" style="font-size: 0.85rem; line-height: 1.2;">
                                <?php echo htmlspecialchars(explode(' ', $sidebarUser['nombre'] ?? 'Usuario')[0]); ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.7rem;">Premium</div>
                        </div>
                        <i class="fas fa-chevron-down ms-2 text-muted d-none d-md-block" style="font-size: 0.7rem;"></i>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" aria-labelledby="profileDropdown" style="min-width: 200px;">
                    <li>
                        <div class="dropdown-header d-flex align-items-center p-3">
                            <div class="flex-shrink-0 me-3">
                                <?php if(!empty($sidebarUser['foto'])): ?>
                                    <img src="<?php echo upload_url($sidebarUser['foto']); ?>" alt="Perfil" width="48" height="48" class="rounded-circle border border-2 border-primary object-fit-cover">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center border border-2 border-white" style="width: 48px; height: 48px;">
                                        <?php echo strtoupper(substr($sidebarUser['nombre'] ?? 'U', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-medium"><?php echo htmlspecialchars($sidebarUser['nombre'] ?? 'Usuario'); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($sidebarUser['email'] ?? ''); ?></div>
                                <div class="badge bg-success bg-opacity-10 text-success mt-1">Usuario</div>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item d-flex align-items-center py-2" href="<?php echo url('perfil'); ?>">
                        <i class="fas fa-user me-3 text-muted" style="width: 16px;"></i>
                        Mi Perfil
                    </a></li>
                    <li><a class="dropdown-item d-flex align-items-center py-2 text-danger" href="<?php echo url('logout'); ?>">
                        <i class="fas fa-arrow-right-from-bracket me-3" style="width: 16px;"></i>
                        Cerrar Sesión
                    </a></li>
                </ul>
            </div>
        </div>
    </header>
    <main class="content-wrapper p-4 overflow-auto flex-grow-1 bg-light">
