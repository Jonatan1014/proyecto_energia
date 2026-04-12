<?php
// src/app/Views/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Monitor de Energía'; ?> - EnergyMonitor</title>
    
    <!-- Meta SEO -->
    <meta name="description" content="Sistema de monitoreo de energía eléctrica en tiempo real. Controla tu consumo y costos con el PZEM-004T.">
    <meta name="theme-color" content="#0f172a">

    <!-- Fuentes: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo asset_url('css/energy.css'); ?>?v=<?php echo time(); ?>">
</head>
<body class="energy-body">
    <?php include __DIR__ . '/alertEvent.php'; ?>
    
    <div class="app-wrapper">
