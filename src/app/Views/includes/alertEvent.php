<?php
// src/app/Views/includes/alertEvent.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Flash messages rendered on auth pages (dashboard uses its own)
if (!empty($_SESSION['error']) && !isset($pageTitle)):
?>
<div class="flash-alert flash-danger" id="flashAlert">
    <i class="fas fa-exclamation-circle"></i>
    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
    <button onclick="this.parentElement.remove()" class="flash-close">&times;</button>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['success']) && !isset($pageTitle)): ?>
<div class="flash-alert flash-success" id="flashAlert">
    <i class="fas fa-check-circle"></i>
    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
    <button onclick="this.parentElement.remove()" class="flash-close">&times;</button>
</div>
<?php endif; ?>

<?php
// Dashboard/settings flash messages (inside page-content)
if (!empty($_SESSION['error']) && isset($pageTitle)):
    $flashError = $_SESSION['error'];
    unset($_SESSION['error']);
endif;
if (!empty($_SESSION['success']) && isset($pageTitle)):
    $flashSuccess = $_SESSION['success'];
    unset($_SESSION['success']);
endif;
?>
