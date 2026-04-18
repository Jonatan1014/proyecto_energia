<?php
// src/app/Views/includes/sidebar.php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$sidebarUser = isset($user) ? $user : ($_SESSION['user'] ?? null);
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo url('dashboard'); ?>" class="sidebar-logo">
            <div class="logo-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <span class="logo-text">EnergyMonitor</span>
        </a>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">MONITOREO</span>
            <a href="<?php echo url('dashboard'); ?>" class="nav-link <?php echo strpos($currentPath, 'dashboard') !== false ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?php echo url('reports'); ?>" class="nav-link <?php echo strpos($currentPath, 'reports') !== false ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Reportes</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">ADMINISTRACIÓN</span>
            <a href="<?php echo url('settings'); ?>" class="nav-link <?php echo strpos($currentPath, 'settings') !== false || strpos($currentPath, 'tariff') !== false ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
            <a href="<?php echo url('perfil'); ?>" class="nav-link <?php echo strpos($currentPath, 'perfil') !== false ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
                <span>Mi Perfil</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">
                <?php if (!empty($sidebarUser['foto'])): ?>
                    <img src="<?php echo upload_url($sidebarUser['foto']); ?>" alt="Perfil">
                <?php else: ?>
                    <span><?php echo strtoupper(substr($sidebarUser['nombre'] ?? 'U', 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($sidebarUser['nombre'] ?? 'Usuario'); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($sidebarUser['email'] ?? ''); ?></div>
            </div>
        </div>
        <a href="<?php echo url('logout'); ?>" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>

<div class="main-content" id="mainContent">
    <!-- Top Bar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="breadcrumb-nav">
                <span class="breadcrumb-icon"><i class="fas fa-bolt"></i></span>
                <span class="breadcrumb-separator">/</span>
                <span class="breadcrumb-current">
                    <?php
                    $pageTitles = [
                        'dashboard' => 'Dashboard',
                        'settings'  => 'Configuración',
                        'perfil'    => 'Mi Perfil',
                    ];
                    $currentPage = 'Dashboard';
                    foreach ($pageTitles as $key => $title) {
                        if (strpos($currentPath, $key) !== false) {
                            $currentPage = $title;
                            break;
                        }
                    }
                    echo $currentPage;
                    ?>
                </span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="device-indicator" id="deviceIndicator">
                <span class="device-dot offline"></span>
                <span class="device-label">Dispositivo</span>
            </div>
        </div>
    </header>
    <main class="page-content">
