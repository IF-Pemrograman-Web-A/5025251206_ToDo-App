<?php
// footer.php - Komponen Footer Modular & Pemuatan Skrip
?>
    <footer role="contentinfo" class="app-footer">
        <div class="footer-content">
            <p>&copy; 2026 The Style Warrior - Iron Citadel Organizational Todo</p>
            <p class="footer-subtext">Tugas 4 Pemrograman Web: Modular PHP, MySQL Database & File Handling</p>
        </div>
    </footer>

    <!-- Data notifikasi untuk diproses Service Worker di script.js -->
    <script id="todos-data" type="application/json">
        <?= json_encode($tasksForNotif ?? []); ?>
    </script>

    <script src="script.js"></script>
</body>
</html>
