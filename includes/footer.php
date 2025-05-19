<?php
require_once __DIR__ . '/functions.php';
?>

<!-- Konten utama akan dimasukkan di sini -->
</main>
</div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="<?= $base_url ?>assets/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= $base_url ?>assets/js/script.js"></script>

<!-- Inline JS untuk halaman tertentu -->
<?php if (isset($inline_js)): ?>
    <script>
        <?= $inline_js ?>
    </script>
<?php endif; ?>
</body>

</html>