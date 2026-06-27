        <!-- Footer -->
        <footer class="mt-5 py-3 border-top text-center text-muted">
            <small>&copy; <?= date('Y'); ?> SPK Seleksi Penerimaan Siswa Baru - Fuzzy Mamdani. All rights reserved.</small>
        </footer>

</div>
</main>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Global App Scripts -->
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('.datatable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
                }
            });

            // Sidebar Toggle for Mobile
            $('#sidebar-toggle').on('click', function() {
                $('#sidebar').toggleClass('active');
            });

            // Close sidebar when clicking outside on mobile
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#sidebar, #sidebar-toggle').length) {
                    $('#sidebar').removeClass('active');
                }
            });
        });
    </script>
    
    <!-- Render Flash Messages -->
    <?php display_flash_message(); ?>
</body>
</html>
