<?php
/**
 * ASHBAB CAFE - Footer Include
 * File: includes/footer.php
 */
?>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- App JS -->
    <script src="<?= APP_URL ?>/assets/js/app.js"></script>
    <?php if (isset($extraJS)) echo $extraJS; ?>
    <script>
        // Initialize AOS
        AOS.init({ duration: 800, once: true, offset: 100 });
        
        // Customer Notifications Polling
        const GLOBAL_APP_URL = '<?= APP_URL ?>';
        function pollCustomerNotifications() {
            fetch(GLOBAL_APP_URL + '/ajax/customer_notifications.php')
            .then(r => r.json())
            .then(res => {
                if (res.success && res.notifications && res.notifications.length > 0) {
                    res.notifications.forEach(n => {
                        // Tampilkan toast
                        showToast(n.title + '<br><small>' + n.message + '</small>', 'info', 5000);
                        // Mark as read
                        fetch(GLOBAL_APP_URL + '/ajax/customer_notifications.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=mark_read&id=' + n.id
                        });
                    });
                }
            })
            .catch(err => console.error(err));
        }
        
        // Poll every 10 seconds
        setInterval(pollCustomerNotifications, 10000);
        setTimeout(pollCustomerNotifications, 1500); // initial delay
    </script>
</body>
</html>
