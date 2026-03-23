<?php
// src/app/Views/includes/alertEvent.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<div class="toast-container" id="toastContainer">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="toast success show">
            <div class="toast-icon"><i class="fas fa-check-circle"></i></div>
            <div class="toast-content">
                <h4>¡Éxito!</h4>
                <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            <div class="toast-progress"></div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="toast error show">
            <div class="toast-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="toast-content">
                <h4>Error</h4>
                <p><?php echo htmlspecialchars($_SESSION['error']); ?></p>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            <div class="toast-progress"></div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
</div>
