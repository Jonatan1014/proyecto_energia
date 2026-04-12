<?php
// src/app/Views/includes/footer.php
?>
    </main> <!-- End page-content -->
</div> <!-- End main-content -->
</div> <!-- End app-wrapper -->

<!-- Overlay for mobile sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<!-- App Scripts -->
<script src="<?php echo asset_url('js/energy-app.js'); ?>?v=<?php echo time(); ?>"></script>

</body>
</html>
