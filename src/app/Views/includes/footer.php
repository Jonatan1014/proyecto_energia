<?php
// src/app/Views/includes/footer.php
?>
    </main> <!-- End content-wrapper -->
</div> <!-- End main-content -->
</div> <!-- End app-container -->

<!-- FAB for Mobile -->
<div class="fab-container d-lg-none">
    <button class="fab-main shadow-lg" onclick="this.closest('.fab-container').classList.toggle('active')">
        <i class="fas fa-plus"></i>
    </button>
    <div class="fab-options">
        <a href="<?php echo url('transaccion/crear?tipo=ingreso'); ?>" class="fab-option bg-success text-white shadow" title="Nuevo Ingreso">
            <i class="fas fa-arrow-up"></i>
        </a>
        <a href="<?php echo url('transaccion/crear?tipo=gasto'); ?>" class="fab-option bg-danger text-white shadow" title="Nuevo Gasto">
            <i class="fas fa-arrow-down"></i>
        </a>
    </div>
</div>

<style>
.fab-container {
    position: fixed;
    bottom: 2rem;
    right: 1.5rem;
    z-index: 1000;
    display: flex;
    flex-direction: column-reverse;
    align-items: center;
    gap: 1rem;
}
.fab-main {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--primary);
    color: white;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    transition: transform 0.3s;
}
.fab-container.active .fab-main {
    transform: rotate(45deg);
}
.fab-options {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    opacity: 0;
    transform: translateY(20px);
    pointer-events: none;
    transition: all 0.3s;
}
.fab-container.active .fab-options {
    opacity: 1;
    transform: translateY(0);
    pointer-events: all;
}
.fab-option {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: transform 0.2s;
}
.fab-option:active {
    transform: scale(0.9);
}
</style>

<!-- Referencias a Scripts Externos -->
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- Chart.js para gráficos vibrantes -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- QRCode.js para generar URLs compartidas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<!-- Scripts principales de la aplicación -->
<script src="<?php echo asset_url('js/app.js'); ?>?v=<?php echo time(); ?>"></script>
<!-- Scripts de gráficos de la aplicación -->
<script src="<?php echo asset_url('js/charts.js'); ?>?v=<?php echo time(); ?>"></script>

</body>
</html>
